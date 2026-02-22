<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterExoticPurchase extends Model
{
    protected $table = 'character_exotic_purchases';
    
    protected $fillable = [
        'character_id',
        'package_id',
        'purchased_at'
    ];

    protected $casts = [
        'purchased_at' => 'datetime'
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function package()
    {
        return $this->belongsTo(ExoticPackage::class, 'package_id', 'package_id');
    }
}
