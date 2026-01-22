<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterWelcomeLogin extends Model
{
    protected $table = 'character_welcome_logins';

    protected $fillable = [
        'character_id',
        'login_count',
        'last_login_date',
        'claimed_days',
    ];

    protected $casts = [
        'last_login_date' => 'date',
        'claimed_days' => 'array',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
