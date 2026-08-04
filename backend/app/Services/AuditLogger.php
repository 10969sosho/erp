<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(Request $request, string $action, Model $entity, ?array $before = null): void
    {
        $tenantId = $request->user()?->tenant_id ?? $entity->tenant_id;

        if (! $tenantId) {
            return;
        }

        AuditEvent::create([
            'tenant_id' => $tenantId,
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'before' => $before,
            'after' => $entity->fresh()?->toArray(),
            'request_id' => $request->header('X-Request-ID'),
            'ip_address' => $request->ip(),
            'occurred_at' => now(),
        ]);
    }
}
