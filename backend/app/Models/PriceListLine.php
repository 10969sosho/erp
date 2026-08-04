<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListLine extends Model
{
    use HasUuids;

    protected $fillable = ['price_list_id', 'item_id', 'minimum_quantity', 'price'];

    protected function casts(): array
    {
        return ['minimum_quantity' => 'decimal:6', 'price' => 'decimal:4'];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
