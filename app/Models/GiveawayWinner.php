<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiveawayWinner extends Model
{
    protected $table = 'giveaway_winners';
    
    protected $fillable = [
        'giveaway_id',
        'character_id',
        'character_name',
        'prize_won',
        'won_at',
        'claimed'
    ];

    protected $casts = [
        'won_at' => 'datetime',
        'claimed' => 'boolean',
        'prize_won' => 'array'
    ];
}
