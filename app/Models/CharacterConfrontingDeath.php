<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterConfrontingDeath extends Model
{
    use HasFactory;

    protected $table = 'character_confronting_death';

    protected $fillable = [
        'character_id',
        'energy',
        'battles_won',
        'claimed_milestones',
    ];

    protected $casts = [
        'claimed_milestones' => 'array',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
