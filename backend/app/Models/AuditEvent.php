<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'actor_id', 'action', 'entity_type', 'entity_id',
        'before', 'after', 'request_id', 'ip_address', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'occurred_at' => 'datetime'];
    }
}
