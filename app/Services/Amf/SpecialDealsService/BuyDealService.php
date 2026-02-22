<?php

namespace App\Services\Amf\SpecialDealsService;

use App\Models\SpecialDeal;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BuyDealService
{
    use DealHelperTrait;

    public function buy($charId, $sessionKey, $dealId)
    {
        try {
            return DB::transaction(function () use ($charId, $dealId) {
                $now = Carbon::now();
                
                $deal = SpecialDeal::lockForUpdate()->find($dealId);
                
                if (!$deal || !$deal->is_active || $deal->start_time > $now || $deal->end_time < $now) {
                    return (object)['status' => 2, 'result' => 'This deal has expired or does not exist.'];
                }

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $deal->price) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                $user->tokens -= $deal->price;
                $user->save();

                $this->processRewards($char, $deal->rewards);

                return (object)[
                    'status' => 1,
                    'rewards' => array_map(function($r) { 
                        $obj = (object)$r;
                        if (!isset($obj->id) && isset($r['item_id'])) $obj->id = $r['item_id'];
                        if (!isset($obj->type)) $obj->type = 'item';
                        return $obj; 
                    }, $deal->rewards ?? [])
                ];
            });

        } catch (\Exception $e) {
            Log::error("SpecialDealsService.buy error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
