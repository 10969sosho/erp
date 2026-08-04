<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceTaxLine extends Model
{
    use HasUuids;

    protected $fillable = ['sales_invoice_id', 'tax_code_id', 'taxable_amount', 'rate', 'tax_amount'];

    protected function casts(): array
    {
        return ['taxable_amount' => 'decimal:4', 'rate' => 'decimal:4', 'tax_amount' => 'decimal:4'];
    }
}
