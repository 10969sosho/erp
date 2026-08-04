<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'warehouse_id', 'number', 'adjustment_date', 'status', 'reason'];

    protected function casts(): array
    {
        return ['adjustment_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class);
    }
}
