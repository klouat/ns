<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'friend_id',
        'status',
        'is_favorite',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function friend()
    {
        return $this->belongsTo(Character::class, 'friend_id');
    }
}
