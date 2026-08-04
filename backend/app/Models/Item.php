<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'sku', 'name', 'type', 'base_unit_id', 'lot_tracking',
        'serial_tracking', 'expiry_tracking', 'minimum_price', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'lot_tracking' => 'boolean',
            'serial_tracking' => 'boolean',
            'expiry_tracking' => 'boolean',
            'minimum_price' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }
}
