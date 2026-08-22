<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Extended Permission model — adds a `group` column used by the
 * PermissionController UI to group permissions per module
 * (accounting / sales / inventory / hr / system).
 */
class Permission extends SpatiePermission
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'group',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => 'string',
        ];
    }

    /**
     * UI-friendly label, e.g. "vouchers.post" → "Post Voucher".
     */
    public function getLabelAttribute(): string
    {
        return ucwords(str_replace(['.', '-'], [' — ', ' '], $this->name));
    }
}
