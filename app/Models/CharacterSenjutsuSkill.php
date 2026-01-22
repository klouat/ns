<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterSenjutsuSkill extends Model
{
    protected $fillable = ['character_id', 'skill_id', 'level', 'type'];
}
