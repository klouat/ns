<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterChuninProgress extends Model
{
    protected $fillable = [
        'character_id',
        'current_stage',
        'stage_status',
        'last_attempt_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
