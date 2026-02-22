<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShadowWarSeason extends Model
{
    protected $fillable = [
        'num',
        'date',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function players()
    {
        return $this->hasMany(ShadowWarPlayer::class, 'season_id');
    }

    public static function activeSeason()
    {
        return static::where('is_active', true)->first();
    }
}
