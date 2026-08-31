<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MediaReturnItem
 * -----------------
 * Paid Return and Free Return are tracked as two separate columns on
 * the same line, per party, within one MediaReturn event.
 * No company_id — scoped through the parent MediaReturn.
 */
class MediaReturnItem extends Model
{
    protected $fillable = [
        'media_return_id',
        'media_party_id',
        'paid_return_quantity',
        'free_return_quantity',
        'total_return_quantity',
    ];

    protected $casts = [
        'paid_return_quantity'  => 'integer',
        'free_return_quantity'  => 'integer',
        'total_return_quantity' => 'integer',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function mediaReturn(): BelongsTo
    {
        return $this->belongsTo(MediaReturn::class, 'media_return_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(MediaParty::class, 'media_party_id');
    }
}
