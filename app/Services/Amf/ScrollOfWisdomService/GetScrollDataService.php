<?php

namespace App\Services\Amf\ScrollOfWisdomService;

use App\Models\Character;
use App\Models\CharacterItem;
use Illuminate\Support\Facades\Log;

class GetScrollDataService
{
    use ScrollHelperTrait;

    public function getData($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            if (!$this->validateSession($char->user_id, $sessionKey)) {
                return (object)['status' => 0, 'error' => 'Session expired!'];
            }

            $scrollItem = CharacterItem::where('character_id', $charId)
                ->where('item_id', 'essential_10')
                ->first();

            $count = $scrollItem ? $scrollItem->quantity : 0;

            return (object)[
                'status' => 1,
                'scrolls' => $count
            ];
        } catch (\Exception $e) {
            Log::error("ScrollOfWisdomService.getData error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
