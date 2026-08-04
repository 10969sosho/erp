<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'bank_account_id', 'statement_number', 'statement_date', 'opening_balance', 'closing_balance', 'status'];

    protected function casts(): array
    {
        return ['statement_date' => 'date', 'opening_balance' => 'decimal:4', 'closing_balance' => 'decimal:4'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
