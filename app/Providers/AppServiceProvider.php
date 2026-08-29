<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Publication;
use App\Models\MediaParty;
use App\Models\PrintPlan;
use App\Models\PrintOrder;
use App\Models\MediaDistribution;
use App\Models\MediaReturn;
use App\Models\MediaCollection;
use App\Policies\VoucherPolicy;
use App\Policies\ModulePolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SQLite testing-এ NOW() support যোগ করুন
        if (config('database.default') === 'sqlite') {
            try {
                DB::connection()->getPdo()->sqliteCreateFunction('NOW', function () {
                    return date('Y-m-d H:i:s');
                });
            } catch (\Exception $e) {
                //
            }
        }

        Gate::policy(Transaction::class, VoucherPolicy::class);
        Gate::policy(Publication::class, ModulePolicy::class);
        Gate::policy(MediaParty::class, ModulePolicy::class);
        Gate::policy(PrintPlan::class, ModulePolicy::class);
        Gate::policy(PrintOrder::class, ModulePolicy::class);
        Gate::policy(MediaDistribution::class, ModulePolicy::class);
        Gate::policy(MediaReturn::class, ModulePolicy::class);
        Gate::policy(MediaCollection::class, ModulePolicy::class);

        Paginator::defaultView('vendor.pagination.tailwind');
    }
}