<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoyaiGachaHistory extends Model
{
    protected $table = 'moyai_gacha_history';
    
    protected $fillable = [
        'character_id',
        'character_name',
        'character_level',
        'reward_id',
        'spin_count',
        'currency',
        'obtained_at'
    ];

    protected $casts = [
        'obtained_at' => 'datetime'
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
