<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'crew_id', 'char_id', 'role', 'contribution',
        'gold_donated', 'token_donated',
        'stamina', 'max_stamina', 'merit',
        'damage', 'boss_kill', 'mini_game_energy',
        'last_mini_game_energy_refill', 'role_switch_cooldown'
    ];

    public function crew()
    {
        return $this->belongsTo(Crew::class, 'crew_id');
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'char_id');
    }
}
