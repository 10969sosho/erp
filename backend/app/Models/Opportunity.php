<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'customer_id', 'owner_id', 'name', 'stage', 'expected_value', 'probability', 'expected_close_date', 'lost_reason'];

    protected function casts(): array
    {
        return ['expected_value' => 'decimal:4', 'expected_close_date' => 'date'];
    }
}
