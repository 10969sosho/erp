<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceTicket extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'customer_id', 'assignee_id', 'number', 'subject', 'priority', 'status', 'due_at', 'description'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }
}
