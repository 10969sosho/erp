<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'company_id', 'code', 'name', 'type', 'parent_id', 'normal_balance', 'status'];
}
