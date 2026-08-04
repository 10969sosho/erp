<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasUuids;

    protected $fillable = ['payment_id', 'purchase_invoice_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4'];
    }
}
