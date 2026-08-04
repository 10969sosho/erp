<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'provider', 'direction', 'idempotency_key', 'status', 'attempts', 'request_payload', 'response_payload', 'error'];

    protected function casts(): array
    {
        return ['request_payload' => 'array', 'response_payload' => 'array'];
    }
}
