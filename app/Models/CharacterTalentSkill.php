<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterTalentSkill extends Model
{
    protected $fillable = [
        'character_id',
        'skill_id',
        'talent_id',
        'level'
    ];
}
