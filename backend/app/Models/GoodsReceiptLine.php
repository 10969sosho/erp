<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    use HasUuids;

    protected $fillable = ['goods_receipt_id', 'purchase_order_line_id', 'quantity', 'accepted_quantity', 'rejected_quantity', 'lot_number', 'expiry_date'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6', 'accepted_quantity' => 'decimal:6', 'rejected_quantity' => 'decimal:6', 'expiry_date' => 'date'];
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }
}
