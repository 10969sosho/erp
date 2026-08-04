<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasUuids;

    protected $fillable = ['branch_id', 'code', 'name', 'status', 'costing_method'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
