<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkashPayment extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id', 'invoice_id', 'customer_id',
        'payment_id', 'trx_id', 'merchant_invoice_number',
        'amount', 'currency', 'status',
        'callback_response', 'execute_response', 'paid_at',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'callback_response' => 'array',
        'execute_response'  => 'array',
        'paid_at'           => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isFailed(): bool { return in_array($this->status, ['failed', 'cancelled'], true); }
}