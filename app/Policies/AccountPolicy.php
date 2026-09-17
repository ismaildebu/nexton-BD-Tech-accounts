class AccountPolicy
{
    // View all accounts
    public function viewAny(User $user): bool
    
    // View specific account
    public function view(User $user, Account $account): bool
    
    // Create account
    public function create(User $user): bool
    
    // Update account (Ledger entries থাকলে ব্লক করা হয়)
    public function update(User $user, Account $account): bool
    
    // Delete account (শুধু খালি অ্যাকাউন্ট)
    public function delete(User $user, Account $account): bool
    
    // Deactivate account
    public function deactivate(User $user, Account $account): bool
}