<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterDailyScratch extends Model
{
    protected $table = 'character_daily_scratch';

    protected $fillable = [
        'character_id',
        'tickets',
        'consecutive_days',
        'last_scratch_date',
    ];

    protected $casts = [
        'last_scratch_date' => 'date',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
