<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShadowWarEnemyCache extends Model
{
    protected $table = 'shadow_war_enemy_cache';

    protected $fillable = [
        'character_id',
        'season_id',
        'enemies',
    ];

    protected $casts = [
        'enemies' => 'array',
    ];
}
