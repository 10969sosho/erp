<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesReturnLine extends Model
{
    use HasUuids;

    protected $fillable = ['sales_return_id', 'sales_order_line_id', 'quantity', 'unit_price', 'line_total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'unit_price' => 'decimal:4', 'line_total' => 'decimal:4'];
    }
}
