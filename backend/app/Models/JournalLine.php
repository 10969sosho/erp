<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    use HasUuids;

    protected $fillable = ['journal_id', 'account_id', 'debit', 'credit', 'description'];

    protected function casts(): array
    {
        return ['debit' => 'decimal:4', 'credit' => 'decimal:4'];
    }
}
