<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportJob extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'requested_by', 'report_key', 'format', 'filters', 'status', 'file_path', 'error'];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
