<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'customer_id', 'sales_order_id', 'number', 'invoice_date', 'due_date', 'subtotal', 'tax_total', 'total', 'status'];

    protected function casts(): array
    {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:4', 'tax_total' => 'decimal:4', 'total' => 'decimal:4'];
    }

    public function taxLines(): HasMany
    {
        return $this->hasMany(SalesInvoiceTaxLine::class);
    }
}
