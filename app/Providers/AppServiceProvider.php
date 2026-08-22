<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   
    public function boot(): void
{
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
