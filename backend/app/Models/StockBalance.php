<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'warehouse_id', 'item_id', 'on_hand', 'reserved', 'average_cost'];

    protected function casts(): array
    {
        return ['on_hand' => 'decimal:6', 'reserved' => 'decimal:6', 'average_cost' => 'decimal:4'];
    }
}
