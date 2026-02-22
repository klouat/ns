<?php

namespace App\Services\Amf\SpecialDealsService;

use App\Models\SpecialDeal;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GetDealsService
{
    public function getDeals($charId, $sessionKey)
    {
        try {
            $now = Carbon::now();
            
            $activeDeals = SpecialDeal::where('is_active', true)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->get();

            $formattedDeals = [];

            foreach ($activeDeals as $deal) {
                $diff = $deal->end_time->diffForHumans(['parts' => 2]);
                
                $formattedDeals[] = (object)[
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'end' => "Ends in: " . $diff,
                    'price' => $deal->price,
                    'items' => array_map(function($r) {
                        $id = $r['id'] ?? ($r['item_id'] ?? '');
                        $qty = $r['q'] ?? ($r['qty'] ?? 1);
                        if ($qty > 1) {
                            return "{$id}:{$qty}";
                        }
                        return $id;
                    }, $deal->rewards ?? [])
                ];
            }
            
            return (object)[
                'status' => 1,
                'deals' => $formattedDeals
            ];

        } catch (\Exception $e) {
            Log::error("SpecialDealsService.getDeals error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
