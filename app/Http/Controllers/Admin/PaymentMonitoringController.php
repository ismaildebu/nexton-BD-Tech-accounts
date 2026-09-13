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
}