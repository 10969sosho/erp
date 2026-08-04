<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'code', 'type', 'legal_name', 'tax_id', 'email', 'phone', 'credit_limit', 'status',
    ];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:4'];
    }
}
