<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class VoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id')
            && $transaction->isDraft();
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id')
            && $transaction->isDraft();
    }

    public function post(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id')
            && $transaction->isApproved();
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id')
            && ! $transaction->isCancelled();
    }

    public function print(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->company_id === (int) session('company_id');
    }
}