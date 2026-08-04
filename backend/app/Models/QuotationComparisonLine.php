<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QuotationComparisonLine extends Model
{
    use HasUuids;

    protected $fillable = ['quotation_comparison_id', 'supplier_quotation_id', 'evaluated_total', 'score', 'notes'];

    protected function casts(): array
    {
        return ['evaluated_total' => 'decimal:4', 'score' => 'decimal:4'];
    }
}
