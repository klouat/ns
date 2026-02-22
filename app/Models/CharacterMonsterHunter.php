<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterMonsterHunter extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'energy',
        'last_energy_reset',
        'boss_id',
    ];
}
