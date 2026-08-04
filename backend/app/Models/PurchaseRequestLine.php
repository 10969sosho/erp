<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    use HasUuids;

    protected $fillable = ['purchase_request_id', 'item_id', 'unit_id', 'quantity', 'estimated_unit_price', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'estimated_unit_price' => 'decimal:4'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
