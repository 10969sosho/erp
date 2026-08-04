<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'customer_id', 'sales_invoice_id', 'sales_return_id', 'number', 'credit_date', 'subtotal', 'tax_total', 'total', 'status', 'reason'];

    protected function casts(): array
    {
        return ['credit_date' => 'date', 'subtotal' => 'decimal:4', 'tax_total' => 'decimal:4', 'total' => 'decimal:4'];
    }
}
