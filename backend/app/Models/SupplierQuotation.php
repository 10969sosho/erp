<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuotation extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'rfq_id', 'supplier_id', 'number', 'currency', 'quotation_date', 'valid_until', 'payment_days', 'subtotal', 'tax_total', 'total', 'status', 'notes'];

    protected function casts(): array
    {
        return ['quotation_date' => 'date', 'valid_until' => 'date', 'subtotal' => 'decimal:4', 'tax_total' => 'decimal:4', 'total' => 'decimal:4'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierQuotationLine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }
}
