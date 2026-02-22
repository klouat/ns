<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoyaiGachaReward extends Model
{
    protected $table = 'moyai_gacha_rewards';
    
    protected $fillable = [
        'reward_id',
        'tier',
        'weight'
    ];
}
