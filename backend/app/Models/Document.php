<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'entity_type', 'entity_id', 'document_type', 'title', 'status'];

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
