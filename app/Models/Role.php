<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extended Role model — mirrors spatie/laravel-permission's Role with
 * explicit typing for the Nexton Accounts domain.
 */
class Role extends SpatieRole
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
