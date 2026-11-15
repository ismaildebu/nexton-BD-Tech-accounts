<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MediaDistributionItem
 * -----------------------
 * One line per Agent/Hawker within a MediaDistribution run.
 * No company_id here — scoping happens through the parent
 * MediaDistribution, matching the PurchaseBillItem/SalesOrderItem
 * convention already used in this codebase.
 */
class MediaDistributionItem extends Model
{
    protected $fillable = [
        'media_distribution_id',
        'media_party_id',
        'paid_quantity',
        'free_percentage',
        'free_quantity',
        'total_quantity',
        'rate',
        'amount',
        'returned_quantity',
        'net_quantity',
    ];

    protected $casts = [
        'paid_quantity'     => 'integer',
        'free_percentage'   => 'decimal:2',
        'free_quantity'     => 'integer',
        'total_quantity'    => 'integer',
        'rate'              => 'decimal:2',
        'amount'            => 'decimal:2',
        'returned_quantity' => 'integer',
        'net_quantity'      => 'integer',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(MediaDistribution::class, 'media_distribution_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(MediaParty::class, 'media_party_id');
    }
}
