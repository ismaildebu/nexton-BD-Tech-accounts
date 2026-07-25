<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function index(DashboardService $dashboardService)
    {
        $companyId = session('company_id');

        // যদি কোনো কোম্পানি সিলেক্ট না থাকে
        if (!$companyId) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'Please select a company first.');
        }

        // Dashboard-এর সকল ডেটা Service থেকে আনুন
        $dashboardData = $dashboardService->getDashboardData($companyId);

        // Dashboard View-তে পাঠান
        return view('dashboard', $dashboardData);
    }
}