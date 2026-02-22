<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterChristmasGacha extends Model
{
    protected $table = 'character_christmas_gacha';
    
    protected $fillable = [
        'character_id',
        'total_spins',
        'claimed_bonuses'
    ];

    protected $casts = [
        'claimed_bonuses' => 'array'
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
