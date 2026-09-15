<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'reference_id',
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'voucher_id',
        'status',
        'verified_by',
        'verified_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'json',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'voucher_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
    

    // Accessors
    public function getIsVerifiedAttribute(): bool
    {
        return $this->status === 'verified';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}