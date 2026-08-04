<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerReceiptAllocation extends Model
{
    use HasUuids;

    protected $fillable = ['customer_receipt_id', 'sales_invoice_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4'];
    }
}
