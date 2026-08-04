<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'number', 'journal_date', 'source_type', 'source_id', 'description', 'status'];

    protected function casts(): array
    {
        return ['journal_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
