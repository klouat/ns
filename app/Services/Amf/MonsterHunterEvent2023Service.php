<?php

namespace App\Services\Amf;

use App\Models\CharacterMonsterHunter;
use App\Helpers\ExperienceHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonsterHunterEvent2023Service
{
    public function getEventData($charId, $sessionKey)
    {
        try {
            // Ensure the record exists
            $record = CharacterMonsterHunter::firstOrCreate(
                ['character_id' => $charId],
                [
                    'energy' => 100,
                    'last_energy_reset' => Carbon::now()->toDateString(),
                    'boss_id' => 'ene_81' // Fallback
                ]
            );

            // Check day change for energy reset
            $today = Carbon::now()->toDateString();
            if ($record->last_energy_reset !== $today) {
                $record->energy = 100;
                $record->last_energy_reset = $today;
                $record->save();
            }
            
            // Validate Boss ID or Pick Random
            if (!$record->boss_id || !\App\Models\MonsterHunterBoss::where('boss_id', $record->boss_id)->exists()) {
                $randomBoss = \App\Models\MonsterHunterBoss::inRandomOrder()->first();
                if ($randomBoss) {
                    $record->boss_id = $randomBoss->boss_id;
                    $record->save();
                }
            }

            return (object)[
                'status' => 1,
                'energy' => $record->energy,
                'boss_id' => $record->boss_id
            ];
        } catch (\Exception $e) {
            Log::error("MonsterHunterEvent2023.getEventData error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function startBattle($charId, $bossId, $clientHash, $sessionKey)
    {
         return \Illuminate\Support\Facades\DB::transaction(function () use ($charId, $bossId) {
            $char = \App\Models\Character::lockForUpdate()->find($charId);
            $record = CharacterMonsterHunter::where('character_id', $charId)->lockForUpdate()->first();
            
            if (!$record) {
                return (object)['status' => 0, 'error' => 'Event data not found.'];
            }

            $energyCost = 10;
            if ($record->energy < $energyCost) {
                 return (object)['status' => 2, 'result' => 'Not enough energy!'];
            }

            $record->energy -= $energyCost;
            $record->save();

            // Generate Battle Code
            $battleCode = md5(uniqid(rand(), true)); 
            
            // Expected Hash from client: sha256(char_id + boss_id) in hex
            // Actually AS says: Hex.fromArray(Crypto.getHash("sha256").hash(Crypto.bytesArray(String(Character.char_id) + String(_loc5_))))
            // _loc5_ is boss_id.
            
            // Return response
            // Client checks: 
            // if(param1.hash != Hex.fromArray(Crypto.getHash("sha256").hash(Crypto.bytesArray(String(Character.christmas_boss_id) + param1.code + String(Character.char_id)))))
            // So we must return a hash that matches: sha256(boss_id . code . char_id)
            
            $serverHash = hash('sha256', $bossId . $battleCode . $charId);

            return (object)[
                'status' => 1,
                'code' => $battleCode,
                'hash' => $serverHash
            ];
         });
    }

    public function finishBattle($charId, $bossId, $battleCode, $score, $hash, $battleData, $sessionKey)
    {
         // Signature updated to match AMF logs: 
         // finishBattle(charId, bossId, battleCode, score, hash, battleData, sessionKey)
         
         return \Illuminate\Support\Facades\DB::transaction(function () use ($charId, $bossId) {
             // Verify we have a record
             $record = CharacterMonsterHunter::where('character_id', $charId)->first();
             if (!$record) return (object)['status' => 0, 'error' => 'Record not found'];
             
             // We can use the bossId passed from client, or trust the DB. 
             // With current setup, Client sends the boss ID it fought.
             
             $boss = \App\Models\MonsterHunterBoss::where('boss_id', $bossId)->first();
             if (!$boss) return (object)['status' => 0, 'error' => 'Boss data not found'];

             $char = \App\Models\Character::lockForUpdate()->find($charId);
             
             // Rewards
             $xpGain = $boss->xp;
             $goldGain = $boss->gold;
             
             $char->xp += $xpGain;
             $char->gold += $goldGain;
             
             // Item Rewards
             $rewards = $boss->rewards; 
             $itemsGiven = [];
             
             if ($rewards && is_array($rewards)) {
                 foreach ($rewards as $itemId => $rate) {
                      // Handle both [item1, item2] and [item1 => 100] formats
                      if (is_numeric($itemId)) {
                           $actualItemId = $rate; 
                           $prob = 100; 
                      } else {
                           $actualItemId = $itemId;
                           $prob = $rate;
                      }

                      if (rand(1, 100) <= $prob) {
                           $itemsGiven[] = $actualItemId;
                           \App\Helpers\ItemHelper::addItem($charId, $actualItemId, 1);
                      }
                 }
             }

             // Level Up Check
             $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
             $char->save();
             
             // Pet XP (20% of character XP gain)
             if ($char->equipped_pet_id) {
                 ExperienceHelper::addEquippedPetXp($charId, floor($xpGain * 0.2));
             }
             
             // Randomize boss for next encounter
             $newBoss = \App\Models\MonsterHunterBoss::inRandomOrder()->first();
             if ($newBoss) {
                 $record->boss_id = $newBoss->boss_id;
                 $record->save();
             }

             // Return structure matching standard BattleSystem for compatibility
             return (object)[
                 'status' => 1,
                 'error' => 0,
                 'result' => [
                     $xpGain,
                     $goldGain,
                     $itemsGiven
                 ],
                 'level' => $char->level,
                 'xp' => $char->xp,
                 'level_up' => $levelUp,
                 'account_tokens' => 0 
             ];
         });
    }


}
