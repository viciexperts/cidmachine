<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = true; // uses created_at/updated_at

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'status',
        'ip',
        'user_agent',
    ];
}
