<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoyaiGachaBonus extends Model
{
    protected $table = 'moyai_gacha_bonuses';
    
    protected $fillable = [
        'requirement',
        'reward_id',
        'quantity',
        'sort_order'
    ];
}
