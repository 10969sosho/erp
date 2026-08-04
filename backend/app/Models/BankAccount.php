<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'code', 'name', 'bank_name', 'account_number', 'currency', 'status'];
}
