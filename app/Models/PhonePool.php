<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhonePool extends Model
{
    protected $fillable = [
        'caller_id',
        'area_code',
        'active',
        'last_assigned_date',
        'last_assigned_campaign',
        'user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_assigned_date' => 'datetime',
    ];

    public function returns()
    {
        return $this->hasMany(PhonePoolReturn::class);
    }
}
