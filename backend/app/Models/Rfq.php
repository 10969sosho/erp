<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfq extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'branch_id', 'purchase_request_id', 'number', 'request_date', 'quotation_deadline', 'status', 'notes'];

    protected function casts(): array
    {
        return ['request_date' => 'date', 'quotation_deadline' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RfqLine::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(RfqSupplier::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
