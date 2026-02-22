<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExoticPackage extends Model
{
    protected $table = 'exotic_packages';
    
    protected $fillable = [
        'package_id',
        'name',
        'price_tokens',
        'items',
        'active'
    ];

    protected $casts = [
        'items' => 'array',
        'active' => 'boolean'
    ];
}
