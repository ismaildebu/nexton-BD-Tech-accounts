<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use App\Services\SSLCommerzPaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SSLCommerzPaymentService $paymentService,
    ) {
    }

    /**
     * Show user's current subscription and billing overview
     */
    public function index(): View
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            $subscription = $this->subscriptionService->ensureHasSubscription($user);
        }

        $plan = $subscription->plan;
        $payments = $subscription->payments()->latest()->paginate(10);

        return view('billing.subscription', compact('subscription', 'plan', 'payments'));
    }

    /**
     * Show available plans for upgrade
     */
    public function showPlans(): View
    {
        $user = auth()->user();
        $currentSubscription = $user->activeSubscription;
        $currentPlanId = $currentSubscription?->plan_id;

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('billing.plans', compact('plans', 'currentPlanId'));
    }

    /**
     * Upgrade/switch to a different plan (old method - kept for backward compatibility)
     */
    public function upgradePlan(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = auth()->user();
        $plan = Plan::findOrFail($request->plan_id);

        try {
            // If free plan, no payment needed
            if ((float) $plan->price === 0.0) {
                $subscription = $this->subscriptionService->activatePlan($user, $plan);

                return redirect()->route('billing.subscription')
                    ->with('success', "আপনি সফলভাবে '{$plan->name}' প্ল্যানে আপগ্রেড হয়েছেন।");
            }

            // For paid plans, redirect to payment initiation
            return redirect()->route('billing.plans.initiate-payment', $plan);
        } catch (\Exception $e) {
            return back()
                ->with('error', 'প্ল্যান আপগ্রেড করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Initiate SSLCommerz payment for plan upgrade
     */
    public function initiatePayment(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = auth()->user();
        $plan = Plan::findOrFail($request->plan_id);

        try {
            // Create new subscription (will be finalized after payment)
            $subscription = $this->subscriptionService->activatePlan($user, $plan);

            // If free plan, no payment needed
            if ((float) $plan->price === 0.0) {
                return redirect()->route('billing.subscription')
                    ->with('success', "আপনি সফলভাবে '{$plan->name}' প্ল্যানে আপগ্রেড হয়েছেন।");
            }

            // Initiate payment gateway
            $gatewayUrl = $this->paymentService->initiatePayment(
                subscriptionId: $subscription->id,
                amount: $plan->price,
                currency: 'BDT',
                userEmail: $user->email,
                userName: $user->name,
                userPhone: $user->phone ?? '01700000000',
            );

            return redirect($gatewayUrl);
        } catch (\Exception $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage());
            return back()
                ->with('error', 'পেমেন্ট প্রক্রিয়া শুরু করতে সমস্যা: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful payment from SSLCommerz
     */
    public function paymentSuccess(Request $request): RedirectResponse
    {
        try {
            $payment = $this->paymentService->handlePaymentSuccess($request->all());

            if (!$payment) {
                return redirect()->route('billing.subscription')
                    ->with('error', 'পেমেন্ট যাচাই করা যায়নি।');
            }

            return redirect()->route('billing.subscription')
                ->with('success', "পেমেন্ট সফলভাবে সম্পন্ন হয়েছে। আপনার সাবস্ক্রিপশন আপডেট হয়েছে।");
        } catch (\Exception $e) {
            Log::error('Payment success handling failed: ' . $e->getMessage());
            return redirect()->route('billing.subscription')
                ->with('error', 'পেমেন্ট প্রক্রিয়াকরণে সমস্যা: ' . $e->getMessage());
        }
    }

    /**
     * Handle failed payment
     */
    public function paymentFailed(Request $request): RedirectResponse
    {
        try {
            $this->paymentService->handlePaymentFailure($request->all());

            return redirect()->route('billing.subscription')
                ->with('error', 'পেমেন্ট ব্যর্থ হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।');
        } catch (\Exception $e) {
            Log::error('Payment failure handling failed: ' . $e->getMessage());
            return redirect()->route('billing.subscription')
                ->with('error', 'পেমেন্ট প্রক্রিয়া ত্রুটি: ' . $e->getMessage());
        }
    }

    /**
     * Handle cancelled payment
     */
    public function paymentCancelled(Request $request): RedirectResponse
    {
        try {
            $this->paymentService->handlePaymentFailure($request->all());

            return redirect()->route('billing.subscription')
                ->with('warning', 'আপনি পেমেন্ট বাতিল করেছেন।');
        } catch (\Exception $e) {
            Log::error('Payment cancellation handling failed: ' . $e->getMessage());
            return redirect()->route('billing.subscription')
                ->with('warning', 'পেমেন্ট বাতিল করা হয়েছে।');
        }
    }

    /**
     * IPN (Instant Payment Notification) callback from SSLCommerz
     * Called server-to-server, not user-dependent
     */
    public function paymentIPN(Request $request): string
    {
        try {
            Log::info('SSLCommerz IPN received', $request->all());

            $payment = $this->paymentService->handlePaymentSuccess($request->all());

            if ($payment) {
                Log::info('IPN processed successfully for payment: ' . $payment->id);
                return 'IPN processed successfully';
            }

            Log::warning('IPN processing failed - payment not found');
            return 'IPN processing failed';
        } catch (\Exception $e) {
            Log::error('SSLCommerz IPN Error: ' . $e->getMessage());
            return 'IPN error: ' . $e->getMessage();
        }
    }

    /**
     * Cancel current subscription
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user = auth()->user();

        try {
            $subscription = $this->subscriptionService->cancelActiveSubscription($user);

            if (!$subscription) {
                return back()->with('error', 'আপনার কোন সক্রিয় সাবস্ক্রিপশন নেই।');
            }

            // Auto-subscribe to free plan after cancellation
            $this->subscriptionService->subscribeToFreePlan($user);

            return redirect()->route('billing.subscription')
                ->with('success', 'আপনার সাবস্ক্রিপশন বাতিল হয়েছে। আপনি ফ্রি প্ল্যানে স্থানান্তরিত হয়েছেন।');
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed: ' . $e->getMessage());
            return back()
                ->with('error', 'সাবস্ক্রিপশন বাতিল করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Show payment history
     */
    public function paymentHistory(): View
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            $subscription = $this->subscriptionService->ensureHasSubscription($user);
        }

        $payments = SubscriptionPayment::where('subscription_id', $subscription->id)
            ->latest()
            ->paginate(20);

        return view('billing.payment-history', compact('subscription', 'payments'));
    }
}