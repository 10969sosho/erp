<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    use HasUuids;

    protected $fillable = ['project_id', 'assignee_id', 'name', 'status', 'progress', 'due_date', 'actual_cost'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'actual_cost' => 'decimal:4'];
    }
}
