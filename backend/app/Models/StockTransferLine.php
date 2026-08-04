<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockTransferLine extends Model
{
    use HasUuids;

    protected $fillable = ['stock_transfer_id', 'item_id', 'unit_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6'];
    }
}
