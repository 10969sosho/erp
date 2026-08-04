<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'branch_id', 'warehouse_id', 'sales_order_id', 'number', 'delivery_date', 'status'];

    protected function casts(): array
    {
        return ['delivery_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class);
    }
}
