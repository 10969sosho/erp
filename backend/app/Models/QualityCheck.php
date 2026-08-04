<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QualityCheck extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'goods_receipt_line_id', 'result', 'accepted_quantity', 'rejected_quantity', 'reason', 'checked_by', 'checked_at'];

    protected function casts(): array
    {
        return ['accepted_quantity' => 'decimal:6', 'rejected_quantity' => 'decimal:6', 'checked_at' => 'datetime'];
    }
}
