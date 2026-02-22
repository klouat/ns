<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterJouninProgress extends Model
{
    protected $table = 'character_jounin_progress';

    protected $fillable = [
        'character_id',
        'current_stage',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
