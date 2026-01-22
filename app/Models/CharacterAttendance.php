<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterAttendance extends Model
{
    protected $table = 'character_attendance';

    protected $fillable = [
        'character_id',
        'month',
        'year',
        'attendance_days',
        'claimed_milestones',
        'last_token_claim',
        'last_xp_claim',
        'last_scroll_claim',
    ];

    protected $casts = [
        'attendance_days' => 'array',
        'claimed_milestones' => 'array',
        'last_token_claim' => 'date',
        'last_xp_claim' => 'date',
        'last_scroll_claim' => 'date',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
