<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonsterHunterBoss extends Model
{
    use HasFactory;

    protected $fillable = [
        'boss_id',
        'xp',
        'gold',
        'rewards',
    ];

    protected $casts = [
        'rewards' => 'array',
    ];
}
