<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Nexton Accounts User Model
 *
 * NOTE: The legacy `role` string column (Admin / Manager / Accountant) is
 * kept for backward compatibility. From now on, the source of truth for
 * access control is the Spatie `roles` / `permissions` system populated
 * by RoleAndPermissionSeeder.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Legacy role options (kept so existing UserController forms
     * keep working while migrating to the permission system).
     *
     * @var array<int, string>
     */
    private const ROLES = ['Admin', 'Manager', 'Accountant'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Role Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Sync the Spatie role from the legacy `role` string column.
     * Call this after creating/updating a user so old code paths keep working.
     */
    public function syncLegacyRole(): void
    {
        // Never downgrade or modify an explicitly assigned Spatie role.
        if ($this->hasRole('super-admin')) {
            return;
        }

        // Do not overwrite manually assigned roles.
        if ($this->roles()->exists()) {
            return;
        }

        $legacyMap = [
            'Admin' => 'admin',
            'Manager' => 'accountant',
            'Accountant' => 'accountant',
        ];

        $newRole = $legacyMap[$this->role] ?? null;

        if ($newRole !== null) {
            $this->assignRole($newRole);
        }
    }

    /**
     * Determine whether the user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Determine whether the user is a Super Admin.
     *
     * Super Admin has no company_id and has the `super-admin` role.
     * Everyone else, including Admin, is locked to exactly one company.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Determine whether the user may access the given company.
     *
     * Super Admin can access every company.
     * Other users can access only their own company.
     */
    public function canAccessCompany(int $companyId): bool
    {
        return $this->isSuperAdmin() || $this->company_id === $companyId;
    }

    /**
     * Get the company assigned to the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all subscriptions this user has ever had.
     *
     * Full history is retained, including cancelled and expired rows.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's current active subscription, if any.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latestOfMany();
    }

    /**
     * Get companies this user owns.
     */
    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }


    
    /**
     * Get transactions created by this user.
     */
    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /**
         * Get transactions posted by this user.
         */
        public function postedTransactions(): HasMany
        {
            return $this->hasMany(Transaction::class, 'posted_by');
        }

    /**
     * Get transactions cancelled by this user.
     */
    public function cancelledTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cancelled_by');
    }
}