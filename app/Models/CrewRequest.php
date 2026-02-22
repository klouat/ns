<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'crew_id', 'char_id'
    ];

    public function crew()
    {
        return $this->belongsTo(Crew::class, 'crew_id');
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'char_id');
    }
}
