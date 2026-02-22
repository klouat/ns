<?php

namespace App\Services\Amf\ExoticPackageService;

use App\Models\Character;
use App\Models\User;
use App\Models\ExoticPackage as ExoticPackageModel;
use App\Models\CharacterExoticPurchase;
use App\Models\CharacterItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PackageService
{
    /**
     * Get all exotic packages data
     * 
     * AMF Call: ExoticPackage.get
     * Parameters: charId, sessionKey
     */
    public function get($charId, $sessionKey)
    {
        $char = Character::find($charId);
        
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        // Get all active packages
        $packages = ExoticPackageModel::where('active', true)->get();
        
        // Format packages for client
        $packagesData = [];
        
        foreach ($packages as $package) {
            $packagesData[$package->package_id] = (object)[
                'name' => $package->name,
                'price' => $package->price_tokens,
                'items' => $package->items
            ];
        }

        return (object)[
            'status' => 1,
            'packages' => (object)$packagesData
        ];
    }

    /**
     * Buy an exotic package
     * 
     * AMF Call: ExoticPackage.buy
     * Parameters: charId, sessionKey, packageId
     */
    public function buy($charId, $sessionKey, $packageId)
    {
        return DB::transaction(function () use ($charId, $packageId) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            
            if (!$char || !$user) {
                return (object)['status' => 0, 'error' => 'Character or user not found'];
            }

            // Get package details
            $package = ExoticPackageModel::where('package_id', $packageId)
                ->where('active', true)
                ->first();
            
            if (!$package) {
                return (object)['status' => 2, 'result' => 'Package not found'];
            }

            // Check if already purchased
            $alreadyPurchased = CharacterExoticPurchase::where('character_id', $charId)
                ->where('package_id', $packageId)
                ->exists();
            
            if ($alreadyPurchased) {
                return (object)['status' => 2, 'result' => 'You already own this package'];
            }

            // Check if player has enough tokens
            if ($user->tokens < $package->price_tokens) {
                return (object)['status' => 2, 'result' => 'Not enough tokens'];
            }

            // Deduct tokens
            $user->tokens -= $package->price_tokens;
            $user->save();

            // Record purchase
            CharacterExoticPurchase::create([
                'character_id' => $charId,
                'package_id' => $packageId,
                'purchased_at' => Carbon::now()
            ]);

            // Give items to character
            $rewards = [];
            foreach ($package->items as $itemId) {
                // Check if it is a skill
                if (strpos($itemId, 'skill_') === 0) {
                    \App\Models\CharacterSkill::firstOrCreate(
                        ['character_id' => $charId, 'skill_id' => $itemId]
                    );
                } else {
                    // Add item to inventory
                    $item = CharacterItem::firstOrCreate(
                        ['character_id' => $charId, 'item_id' => $itemId],
                        ['quantity' => 0, 'category' => 'item']
                    );
                    
                    $item->quantity += 1;
                    $item->save();
                }

                // Format for client reward display
                $rewards[] = $itemId;
            }

            return (object)[
                'status' => 1,
                'rewards' => $rewards
            ];
        });
    }
}
