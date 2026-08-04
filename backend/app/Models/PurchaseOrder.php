<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'branch_id', 'supplier_id', 'purchase_request_id', 'supplier_quotation_id', 'number', 'currency', 'order_date', 'expected_date', 'payment_days', 'subtotal', 'tax_total', 'total', 'status', 'notes'];

    protected function casts(): array
    {
        return ['order_date' => 'date', 'expected_date' => 'date', 'subtotal' => 'decimal:4', 'tax_total' => 'decimal:4', 'total' => 'decimal:4'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }
}
