<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShadowWarPreset extends Model
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
        'skin_color',
        'skills',
        'pet_swf',
        'pet_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
