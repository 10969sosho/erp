<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'definition_id', 'entity_type', 'entity_id', 'status', 'current_step'];

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
