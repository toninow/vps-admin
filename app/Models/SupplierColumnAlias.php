<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierColumnAlias extends Model
{
    protected $fillable = [
        'target_field',
        'alias',
    ];
}
