<?php

use App\Providers\AppServiceProvider;
use App\Models\Transaction;
use App\Policies\VoucherPolicy;

return [
    AppServiceProvider::class,
];

// boot() method এর ভেতরে:
Gate::policy(Transaction::class, VoucherPolicy::class);

// Pagination view:
Paginator::defaultView('vendor.pagination.tailwind');