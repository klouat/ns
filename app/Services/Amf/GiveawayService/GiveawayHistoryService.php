<?php

namespace App\Services\Amf\GiveawayService;

use App\Models\Giveaway;
use Carbon\Carbon;

class GiveawayHistoryService
{
    public function history($charId, $sessionKey)
    {
        $history = Giveaway::where('processed', true)
            ->where('end_at', '<=', Carbon::now())
            ->orderBy('end_at', 'desc')
            ->limit(20)
            ->get();

        $formattedHistory = [];
        foreach ($history as $h) {
            $formattedHistory[] = (object)[
                'id' => $h->id,
                'title' => $h->title,
                'description' => $h->description,
                'ended_at' => $h->end_at->format('Y-m-d'),
                'prizes' => array_values($h->prizes ?? [])
            ];
        }

        return (object)[
            'status' => 1,
            'giveaway' => $formattedHistory
        ];
    }
}
