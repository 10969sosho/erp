<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'branch_id', 'warehouse_id', 'sales_order_id', 'sales_invoice_id', 'number', 'return_date', 'status', 'reason'];

    protected function casts(): array
    {
        return ['return_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class);
    }
}
