<?php

namespace App\Services\Amf\ChristmasEvent2021Service;

use App\Models\MoyaiGachaHistory;
use Illuminate\Support\Facades\Log;

class HistoryService
{
    public function getPersonalGachaHistory($charId, $sessionKey)
    {
        try {
            $histories = MoyaiGachaHistory::where('character_id', $charId)
                ->orderBy('obtained_at', 'desc')
                ->limit(100)
                ->get();

            $formattedHistories = [];
            
            foreach ($histories as $history) {
                $formattedHistories[] = (object)[
                    'id' => $history->character_id,
                    'name' => $history->character_name,
                    'level' => $history->character_level,
                    'reward' => $history->reward_id,
                    'spin' => $history->spin_count,
                    'currency' => $history->currency,
                    'obtained_at' => $history->obtained_at->format('Y-m-d H:i:s')
                ];
            }

            return (object)[
                'status' => 1,
                'histories' => $formattedHistories
            ];
        } catch (\Exception $e) {
            Log::error('ChristmasEvent2021Service.HistoryService.getPersonalGachaHistory error: ' . $e->getMessage());
            return (object)[
                'status' => 1,
                'histories' => []
            ];
        }
    }

    public function getGlobalGachaHistory($charId, $sessionKey)
    {
        try {
            $histories = MoyaiGachaHistory::orderBy('obtained_at', 'desc')
                ->limit(100)
                ->get();

            $formattedHistories = [];
            
            foreach ($histories as $history) {
                $formattedHistories[] = (object)[
                    'id' => $history->character_id,
                    'name' => $history->character_name,
                    'level' => $history->character_level,
                    'reward' => $history->reward_id,
                    'spin' => $history->spin_count,
                    'currency' => $history->currency,
                    'obtained_at' => $history->obtained_at->format('Y-m-d H:i:s')
                ];
            }

            return (object)[
                'status' => 1,
                'histories' => $formattedHistories
            ];
        } catch (\Exception $e) {
            Log::error('ChristmasEvent2021Service.HistoryService.getGlobalGachaHistory error: ' . $e->getMessage());
            return (object)[
                'status' => 1,
                'histories' => []
            ];
        }
    }
}
