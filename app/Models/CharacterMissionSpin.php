<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterMissionSpin extends Model
{
    protected $fillable = [
        'character_id',
        'spins_available',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
