<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterHuntingHouse extends Model
{
    use HasFactory;

    protected $table = 'character_hunting_house';

    protected $fillable = [
        'character_id',
        'last_daily_claim_date',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
