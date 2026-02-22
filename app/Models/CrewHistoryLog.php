<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewHistoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'crew_id', 'message'
    ];

    public function crew()
    {
        return $this->belongsTo(Crew::class, 'crew_id');
    }
}
