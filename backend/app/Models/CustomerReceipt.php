<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReceipt extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'customer_id', 'number', 'method', 'receipt_date', 'amount', 'status', 'notes'];

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'amount' => 'decimal:4'];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerReceiptAllocation::class);
    }
}
