<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewSeason extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'phase1_start_at' => 'datetime',
        'phase1_end_at' => 'datetime',
        'phase2_end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function rewards()
    {
        return $this->hasMany(CrewSeasonReward::class);
    }
}
