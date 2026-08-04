<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    use HasUuids;

    protected $fillable = ['stock_adjustment_id', 'item_id', 'unit_id', 'quantity_delta', 'unit_cost'];

    protected function casts(): array
    {
        return ['quantity_delta' => 'decimal:6', 'unit_cost' => 'decimal:4'];
    }
}
