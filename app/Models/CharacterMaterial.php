<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'item_id',
        'quantity',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
