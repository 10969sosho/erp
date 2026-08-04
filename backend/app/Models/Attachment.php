<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'document_id', 'file_name', 'storage_path', 'mime_type', 'size', 'sha256', 'scan_status'];
}
