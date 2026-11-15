<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Platform-level subscription payment monitoring.
 *
 * Every route using this controller MUST be behind the `super-admin`
 * middleware (see routes/admin.php).
 */
class PaymentMonitoringController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Show all subscription payments dashboard
     */
    public function index(Request $request): View
    {
        $query = SubscriptionPayment::with('subscription.user', 'subscription.plan');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('subscription.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest()->paginate(20);

        // Summary stats
        $stats = [
            'total_payments' => SubscriptionPayment::count(),
            'total_amount' => SubscriptionPayment::sum('amount'),
            'paid_count' => SubscriptionPayment::where('status', 'paid')->count(),
            'pending_count' => SubscriptionPayment::where('status', 'pending')->count(),
            'failed_count' => SubscriptionPayment::where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    /**
     * Show payment details
     */
    public function show(SubscriptionPayment $payment): View
    {
        $payment->load('subscription.user', 'subscription.plan');

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * User subscription payments
     */
    public function userPayments(User $user): View
    {
        $subscriptions = $user->subscriptions()->with('payments')->latest()->get();
        $payments = SubscriptionPayment::whereIn(
            'subscription_id',
            $user->subscriptions()->pluck('id')
        )->latest()->get();

        return view('admin.payments.user', compact('user', 'subscriptions', 'payments'));
    }

    /**
     * Verify a pending payment and activate its pending subscription.
     *
     * Atomic: either the payment is marked paid AND the subscription is
     * activated, or nothing changes. Only a pending payment can be verified,
     * so a double click / replay / failed payment cannot be re-verified.
     */
    public function verifyPayment(SubscriptionPayment $payment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($payment): void {
                $locked = SubscriptionPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if ($locked->status !== SubscriptionPayment::STATUS_PENDING) {
                    throw new RuntimeException('Only a pending payment can be verified.');
                }

                $subscription = Subscription::query()->findOrFail($locked->subscription_id);

                $locked->update([
                    'status' => SubscriptionPayment::STATUS_PAID,
                    'paid_at' => now(),
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'verified_by' => auth()->id(),
                        'verified_at' => now()->toIso8601String(),
                    ]),
                ]);

                // Cancels the user's current active plan and activates this one,
                // keeping the "one active subscription per user" invariant.
                $this->subscriptionService->activatePendingSubscription($subscription);
            });

            Log::info('Payment verified and subscription activated', [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'verified_by' => auth()->id(),
            ]);

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', "পেমেন্ট #{$payment->id} সফলভাবে যাচাই হয়েছে। সাবস্ক্রিপশন সক্রিয় করা হয়েছে।");
        } catch (RuntimeException $e) {
            return back()->with('error', 'পেমেন্ট যাচাই করা যায়নি: ' . $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Payment verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'পেমেন্ট যাচাই করতে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।');
        }
    }

    /**
     * Reject a pending payment and cancel its still-pending subscription.
     *
     * An already active subscription is never touched here.
     */
    public function rejectPayment(SubscriptionPayment $payment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($payment): void {
                $locked = SubscriptionPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if ($locked->status !== SubscriptionPayment::STATUS_PENDING) {
                    throw new RuntimeException('Only a pending payment can be rejected.');
                }

                $locked->update([
                    'status' => SubscriptionPayment::STATUS_FAILED,
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now()->toIso8601String(),
                    ]),
                ]);

                $subscription = Subscription::query()
                    ->lockForUpdate()
                    ->findOrFail($locked->subscription_id);

                if ($subscription->status === Subscription::STATUS_PENDING) {
                    $subscription->update([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ]);
                }
            });

            Log::info('Payment rejected', [
                'payment_id' => $payment->id,
                'rejected_by' => auth()->id(),
            ]);

            return redirect()->route('admin.payments.show', $payment)
                ->with('warning', "পেমেন্ট #{$payment->id} প্রত্যাখ্যান করা হয়েছে।");
        } catch (RuntimeException $e) {
            return back()->with('error', 'পেমেন্ট প্রত্যাখ্যান করা যায়নি: ' . $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Payment rejection failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'পেমেন্ট প্রত্যাখ্যান করতে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।');
        }
    }
}
