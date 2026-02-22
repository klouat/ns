<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Giveaway extends Model
{
    protected $table = 'giveaways';
    
    protected $fillable = [
        'title',
        'description',
        'prizes',
        'requirements',
        'start_at',
        'end_at',
        'processed'
    ];

    protected $casts = [
        'prizes' => 'array',
        'requirements' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'processed' => 'boolean'
    ];

    public function participants()
    {
        return $this->hasMany(CharacterGiveaway::class);
    }

    public function winners()
    {
        return $this->hasMany(GiveawayWinner::class);
    }
}
