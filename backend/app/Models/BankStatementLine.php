<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    use HasUuids;

    protected $fillable = ['bank_statement_id', 'transaction_date', 'reference', 'description', 'amount', 'direction', 'status', 'matched_payment_id'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:4'];
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }
}
