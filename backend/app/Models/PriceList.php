<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'code', 'name', 'currency', 'effective_from', 'effective_to', 'status'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PriceListLine::class);
    }
}
