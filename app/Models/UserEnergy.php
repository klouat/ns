<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEnergy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'energy_grade_s',
        'max_energy_grade_s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
