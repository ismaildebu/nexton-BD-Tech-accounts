<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    // View all accounts
    public function viewAny(User $user): bool
    {
        return $user->can('accounts.view');
    }

    // View specific account
    public function view(User $user, Account $account): bool
    {
        return (int) $account->company_id === (int) session('company_id')
            && $user->can('accounts.view');
    }

    // Create account
    public function create(User $user): bool
    {
        return $user->can('accounts.create');
    }

    // Update account (Ledger entries থাকলে ব্লক করা হয়)
    public function update(User $user, Account $account): bool
    {
        return (int) $account->company_id === (int) session('company_id')
            && ! $account->hasTransactions()
            && $user->can('accounts.edit');
    }

    // Delete account (শুধু খালি অ্যাকাউন্ট)
    public function delete(User $user, Account $account): bool
    {
        return (int) $account->company_id === (int) session('company_id')
            && $account->canDelete()
            && $user->can('accounts.delete');
    }

    // Deactivate account
    public function deactivate(User $user, Account $account): bool
    {
        return (int) $account->company_id === (int) session('company_id')
            && $user->can('accounts.edit');
    }
}