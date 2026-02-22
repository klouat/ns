<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DragonGachaHistory extends Model
{
    protected $fillable = [
        'character_id',
        'character_name',
        'level',
        'reward',
        'spin_count',
        'obtained_at'
    ];

    protected $casts = [
        'obtained_at' => 'datetime',
    ];
}
