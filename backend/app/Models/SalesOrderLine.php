<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesOrderLine extends Model
{
    use HasUuids;

    protected $fillable = ['sales_order_id', 'item_id', 'unit_id', 'quantity', 'unit_price', 'tax_rate', 'line_total', 'delivered_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'unit_price' => 'decimal:4', 'tax_rate' => 'decimal:4', 'line_total' => 'decimal:4', 'delivered_quantity' => 'decimal:6'];
    }
}
