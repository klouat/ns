<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReward extends Model
{
    protected $table = 'attendance_rewards';

    protected $fillable = [
        'price',
        'item',
    ];
}
