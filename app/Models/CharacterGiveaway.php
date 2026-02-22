<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterGiveaway extends Model
{
    protected $table = 'character_giveaways';
    
    protected $fillable = [
        'character_id',
        'giveaway_id',
        'joined_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime'
    ];

    public function giveaway()
    {
        return $this->belongsTo(Giveaway::class);
    }
}
