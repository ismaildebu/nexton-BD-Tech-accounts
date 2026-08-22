<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintOrder extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const STATUS_DRAFT    = 'Draft';
    public const STATUS_ORDERED  = 'Ordered';
    public const STATUS_PRINTING = 'Printing';
    public const STATUS_PRINTED  = 'Printed';
    public const STATUS_RECEIVED = 'Received';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'company_id',
        'publication_id',
        'print_plan_id',
        'vendor_id',
        'order_number',
        'order_date',
        'print_date',
        'ordered_quantity',
        'printed_quantity',
        'received_quantity',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date'         => 'date',
        'print_date'         => 'date',
        'ordered_quantity'   => 'integer',
        'printed_quantity'   => 'integer',
        'received_quantity'  => 'integer',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function printPlan(): BelongsTo
    {
        return $this->belongsTo(PrintPlan::class);
    }

    /**
     * The printing press/vendor — reuses the existing Vendor model.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
