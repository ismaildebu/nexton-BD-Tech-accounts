<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

/**
 * Nexton Accounts User Model
 *
 * NOTE: The legacy `role` string column (Admin / Manager / Accountant) is
 * kept for backward compatibility. From now on the source of truth for
 * access control is the spatie `roles` / `permissions` system populated
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
    | Legacy role helpers (bridge to the new permission system)
    |--------------------------------------------------------------------------
    */

    /**
     * Sync the spatie role from the legacy `role` string column.
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
        'Admin'      => 'admin',
        'Manager'    => 'manager',
        'Accountant' => 'accountant',
    ];

    $newRole = $legacyMap[$this->role] ?? null;

    if ($newRole !== null) {
        $this->assignRole($newRole);
    }
}

    public function isAdministrator(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Super Admin has no company_id (global scope) and the 'super-admin' role.
     * Everyone else (Admin included) is locked to exactly one company.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * True if this user may view/edit the given company.
     * Super Admin can access every company; everyone else only their own.
     */
    public function canAccessCompany(int $companyId): bool
    {
        return $this->isSuperAdmin() || $this->company_id === $companyId;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function postedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'posted_by');
    }

    public function cancelledTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cancelled_by');
    }
}
