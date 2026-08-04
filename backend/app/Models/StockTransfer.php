<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'source_warehouse_id', 'destination_warehouse_id', 'number', 'transfer_date', 'status', 'reason'];

    protected function casts(): array
    {
        return ['transfer_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }
}
