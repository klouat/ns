<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterGearPreset extends Model
{
    protected $fillable = [
        'character_id',
        'name',
        'weapon',
        'clothing',
        'hair',
        'back_item',
        'accessory',
        'hair_color',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
