<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeliveryLine extends Model
{
    use HasUuids;

    protected $fillable = ['delivery_id', 'sales_order_line_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:6'];
    }
}
