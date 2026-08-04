<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'requested_by', 'import_type', 'file_path', 'status', 'total_rows', 'processed_rows', 'errors'];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }
}
