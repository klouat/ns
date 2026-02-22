<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Castle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'owner_crew_id', 'wall_hp', 'defender_hp'
    ];

    public function ownerCrew()
    {
        return $this->belongsTo(Crew::class, 'owner_crew_id');
    }
}
