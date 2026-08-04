<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'code', 'name', 'rate', 'effective_from', 'effective_to', 'status'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:4', 'effective_from' => 'date', 'effective_to' => 'date'];
    }
}
