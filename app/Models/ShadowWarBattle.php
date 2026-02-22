<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShadowWarBattle extends Model
{
    protected $fillable = [
        'battle_code',
        'attacker_id',
        'defender_id',
        'season_id',
        'trophies_change',
        'is_finished',
    ];

    protected $casts = [
        'is_finished' => 'boolean',
    ];

    public function attacker()
    {
        return $this->belongsTo(Character::class, 'attacker_id');
    }

    public function defender()
    {
        return $this->belongsTo(Character::class, 'defender_id');
    }
}
