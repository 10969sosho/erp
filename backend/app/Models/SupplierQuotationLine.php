<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationLine extends Model
{
    use HasUuids;

    protected $fillable = ['supplier_quotation_id', 'rfq_line_id', 'quantity', 'unit_price', 'tax_rate', 'line_total', 'promised_date'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'unit_price' => 'decimal:4', 'tax_rate' => 'decimal:4', 'line_total' => 'decimal:4', 'promised_date' => 'date'];
    }

    public function rfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLine::class);
    }
}
