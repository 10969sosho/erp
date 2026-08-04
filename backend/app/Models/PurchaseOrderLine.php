<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use HasUuids;

    protected $fillable = ['purchase_order_id', 'item_id', 'unit_id', 'quantity', 'unit_price', 'tax_rate', 'line_total', 'received_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'unit_price' => 'decimal:4', 'tax_rate' => 'decimal:4', 'line_total' => 'decimal:4', 'received_quantity' => 'decimal:6'];
    }
}
