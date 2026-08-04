<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationComparison extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'rfq_id', 'number', 'status', 'selected_quotation_id', 'decision_notes'];

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationComparisonLine::class);
    }
}
