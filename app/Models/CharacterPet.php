<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterPet extends Model
{
    protected $fillable = [
        'character_id',
        'pet_swf',
        'pet_name',
        'pet_level',
        'pet_xp',
        'pet_mp',
        'pet_skills'
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
