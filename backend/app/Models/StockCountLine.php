<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockCountLine extends Model
{
    use HasUuids;

    protected $fillable = ['stock_count_id', 'item_id', 'system_quantity', 'counted_quantity', 'variance'];

    protected function casts(): array
    {
        return ['system_quantity' => 'decimal:6', 'counted_quantity' => 'decimal:6', 'variance' => 'decimal:6'];
    }
}
