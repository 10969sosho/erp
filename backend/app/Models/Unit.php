<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasUuids;

    protected $fillable = ['tenant_id', 'code', 'name', 'precision', 'status'];
}
