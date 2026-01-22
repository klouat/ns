<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mail extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'sender_name',
        'title',
        'body',
        'type',
        'rewards',
        'is_viewed',
        'is_claimed',
    ];

    protected $casts = [
        'is_viewed' => 'boolean',
        'is_claimed' => 'boolean',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
