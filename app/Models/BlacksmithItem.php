<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacksmithItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'materials', // JSON array of item IDs
        'quantities', // JSON array of quantities
        'gold_price',
        'token_price',
        'req_weapon'
    ];

    protected $casts = [
        'materials' => 'array',
        'quantities' => 'array'
    ];
}
