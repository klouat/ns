<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterLimitedStore extends Model
{
    protected $fillable = ['character_id', 'items', 'end_time', 'discount', 'refresh_count'];

    protected $casts = [
        'items' => 'array',
        'end_time' => 'datetime',
    ];
}
