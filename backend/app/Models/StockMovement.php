<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'warehouse_id', 'item_id', 'unit_id', 'movement_type', 'direction', 'quantity', 'unit_cost', 'source_type', 'source_id', 'lot_number', 'expiry_date', 'occurred_at'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'unit_cost' => 'decimal:4', 'expiry_date' => 'date', 'occurred_at' => 'datetime'];
    }
}
