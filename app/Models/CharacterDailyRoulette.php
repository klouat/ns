<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterDailyRoulette extends Model
{
    protected $table = 'character_daily_roulette';

    protected $fillable = [
        'character_id',
        'consecutive_days',
        'last_spin_date',
    ];

    protected $casts = [
        'last_spin_date' => 'date',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
