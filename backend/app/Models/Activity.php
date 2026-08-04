<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'user_id', 'subject', 'activity_type', 'status', 'due_at', 'notes', 'related_type', 'related_id'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }
}
