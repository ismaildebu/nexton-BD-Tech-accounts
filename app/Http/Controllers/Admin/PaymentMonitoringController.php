<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMonitoringController extends Controller
{
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
     * Verify and activate payment
     */
    public function verifyPayment(SubscriptionPayment $payment): RedirectResponse
    {
        try {
            // Mark payment as PAID
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Get subscription and activate it
            $subscription = $payment->subscription;
            $subscription->update([
                'status' => 'active',
                'started_at' => now(),
            ]);

            Log::info('Payment verified and subscription activated', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', "পেমেন্ট #$payment->id সফলভাবে যাচাই হয়েছে। সাবস্ক্রিপশন সক্রিয় করা হয়েছে।");
        } catch (\Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            return back()
                ->with('error', 'পেমেন্ট যাচাই করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Reject payment and cancel subscription
     */
    public function rejectPayment(SubscriptionPayment $payment): RedirectResponse
    {
        try {
            // Mark payment as FAILED
            $payment->update([
                'status' => 'failed',
            ]);

            // Cancel subscription
            $subscription = $payment->subscription;
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::info('Payment rejected and subscription cancelled', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            return redirect()->route('admin.payments.show', $payment)
                ->with('warning', "পেমেন্ট #$payment->id প্রত্যাখ্যান করা হয়েছে। সাবস্ক্রিপশন বাতিল করা হয়েছে।");
        } catch (\Exception $e) {
            Log::error('Payment rejection failed: ' . $e->getMessage());
            return back()
                ->with('error', 'পেমেন্ট প্রত্যাখ্যান করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }
}