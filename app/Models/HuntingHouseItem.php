<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HuntingHouseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'category',
        'materials', // JSON array of item IDs
        'quantities', // JSON array of quantities
        'sort_order',
        'expires_at'
    ];

    protected $casts = [
        'materials' => 'array',
        'quantities' => 'array'
    ];
}
