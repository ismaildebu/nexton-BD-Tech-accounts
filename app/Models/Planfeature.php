<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_key',
        'limit_value',
        'is_enabled',
    ];

    protected function casts(): array  // ✅ { যোগ করা হয়েছে
    {
        return [
            'limit_value' => 'integer',
            'is_enabled'  => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}