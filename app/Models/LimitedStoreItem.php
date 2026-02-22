<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LimitedStoreItem extends Model
{
    protected $fillable = ['item_id', 'price_token', 'price_emblem', 'category', 'group_id', 'sort_order', 'is_active'];
}
