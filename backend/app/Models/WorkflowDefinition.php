<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'entity_type', 'name', 'steps', 'active'];

    protected function casts(): array
    {
        return ['steps' => 'array', 'active' => 'boolean'];
    }
}
