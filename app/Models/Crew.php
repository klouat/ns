<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'master_id', 'elder_id', 'level', 'golds', 'tokens',
        'kushi_dango', 'tea_house', 'bath_house', 'training_centre',
        'max_members', 'announcement', 'last_renamed_at'
    ];

    public function members()
    {
        return $this->hasMany(CrewMember::class, 'crew_id');
    }

    public function requests()
    {
        return $this->hasMany(CrewRequest::class, 'crew_id');
    }

    public function master()
    {
        return $this->belongsTo(Character::class, 'master_id');
    }

    public function elder()
    {
        return $this->belongsTo(Character::class, 'elder_id');
    }
}
