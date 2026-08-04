<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'warehouse_id', 'number', 'count_type', 'status', 'count_date', 'reason'];

    protected function casts(): array
    {
        return ['count_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }
}
