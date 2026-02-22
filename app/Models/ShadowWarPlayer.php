<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShadowWarPlayer extends Model
{
    protected $fillable = [
        'character_id',
        'season_id',
        'squad',
        'trophy',
        'rank',
        'energy',
        'show_profile',
    ];

    protected $casts = [
        'show_profile' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function season()
    {
        return $this->belongsTo(ShadowWarSeason::class, 'season_id');
    }
}
