<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhonePoolReturn extends Model
{
    protected $fillable = [
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
