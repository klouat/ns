<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'gender',
        'hair_style',
        'hair_color',
        'skin_color',
        'equipment_weapon',
        'equipment_back',
        'equipment_clothing',
        'equipment_accessory',
        'equipment_skills',
        'rank',
        'gold',
        'claimed_welcome_rewards',
        'point_wind',
        'point_fire',
        'point_lightning',
        'point_water',
        'point_earth',
        'point_free',
        'tp',
        'prestige',
        'element_1',
        'element_2',
        'element_3',
        'talent_1',
        'talent_2',
        'talent_3',
        'equipped_pet_id',
        'is_recruitable',
        'senjutsu',
        'character_ss',
        'equipped_senjutsu_skills',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gearPresets()
    {
        return $this->hasMany(CharacterGearPreset::class);
    }
}