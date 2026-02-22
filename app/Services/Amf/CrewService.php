<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\CrewRequest;
use App\Models\Castle;
use App\Models\User;
use App\Models\CharacterPet;
use App\Helpers\GameDataHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CrewSeason;
use App\Models\CrewSetting;
use App\Models\CrewMinigameReward;

class CrewService
{
    // Define constants for Roles
    const ROLE_MASTER = 1;
    const ROLE_ELDER = 2;
    const ROLE_MEMBER = 3;

    // --- Core Crew Management ---

    public function getSeason()
    {
        $data = $this->getInternalSeasonData();
        return (object)[
            'season' => (object)[
                'phase' => $data->phase,
                'timestamp' => $data->remaining
            ],
            'timestamp' => now()->timestamp,
            'phase' => $data->phase,
            'rp1' => $data->rp1,
            'rp2' => $data->rp2
        ];
    }

    private function getInternalSeasonData()
    {
        $now = now();
        $season = CrewSeason::where('is_active', true)
            ->where('phase1_start_at', '<=', $now)
            ->where('phase2_end_at', '>=', $now)
            ->with('rewards')
            ->first();
            
        if (!$season) {
            return (object)[
                'phase' => 1,
                'remaining' => 0,
                'rp1' => [],
                'rp2' => []
            ];
        }
        
        $currentPhase = 1;
        $phaseEndTime = $season->phase1_end_at;

        if ($now->greaterThanOrEqualTo($season->phase1_end_at)) {
            $currentPhase = 2;
            $phaseEndTime = $season->phase2_end_at;
        }
        
        // Calculate remaining seconds for the current phase
        $remaining = max(0, $phaseEndTime->getTimestamp() - $now->getTimestamp());
        
        $rp1 = $season->rewards->where('phase', 1)->sortBy('sort_order')->pluck('reward_id')->values()->toArray();
        $rp2 = $season->rewards->where('phase', 2)->sortBy('sort_order')->pluck('reward_id')->values()->toArray();
        
        return (object)[
            'phase' => $currentPhase,
            'remaining' => $remaining,
            'rp1' => $rp1,
            'rp2' => $rp2
        ];
    }
    
    public function getTokenPool()
    {
        return (object)[
            't' => (int)CrewSetting::getValue('token_pool', 100000),
            'base' => (int)CrewSetting::getValue('token_pool_base', 50000)
        ];
    }

    public function login($charId, $sessionKey)
    {
        // Validate session and return OK
        $char = Character::find($charId);
        if (!$char) {
            return (object)['errorMessage' => 'Character not found'];
        }
        
        // Generate or use existing access token
        // For now, use the session key as the token
        return (object)['access_token' => $sessionKey];
    }

    public function getCrewData($charId, $sessionKey)
    {
        Log::info('getCrewData called', ['char_id' => $charId, 'session_key' => $sessionKey]);
        
        $character = Character::find($charId);
        if (!$character) {
            Log::error('getCrewData: Character not found', ['char_id' => $charId]);
            return (object)['errorMessage' => 'Character not found'];
        }
        
        Log::info('getCrewData: Character found', ['char_id' => $charId, 'name' => $character->name]);
        
        $member = CrewMember::where('char_id', $charId)->with('crew')->first();

        if (!$member) {
            Log::info('getCrewData: User not in crew, returning 404', ['char_id' => $charId]);
            return (object)['code' => 404, 'errorMessage' => 'You are not in a crew'];
        }

        Log::info('getCrewData: Member found', ['crew_id' => $member->crew_id]);

        $crew = $member->crew;
        
        // Get master and elder names
        $masterChar = Character::find($crew->master_id);
        $elderChar = $crew->elder_id ? Character::find($crew->elder_id) : null;
        
        $crewData = [
            'id' => $crew->id,
            'name' => $crew->name,
            'master_id' => $crew->master_id,
            'master_name' => $masterChar ? $masterChar->name : 'Unknown',
            'elder_id' => $crew->elder_id,
            'elder_name' => $elderChar ? $elderChar->name : '',
            'level' => $crew->level,
            'golds' => $crew->golds,
            'tokens' => $crew->tokens,
            'members' => $crew->members()->count(),
            'max_members' => $crew->max_members,
            'announcement' => $crew->announcement ?? '',
            'kushi_dango' => $crew->kushi_dango,
            'tea_house' => $crew->tea_house,
            'bath_house' => $crew->bath_house,
            'training_centre' => $crew->training_centre,
        ];
        
        $charData = [
            'char_id' => $member->char_id,
            'role' => $member->role,
            'contribution' => $member->contribution,
            'stamina' => $member->stamina,
            'max_stamina' => $member->max_stamina,
            'merit' => $member->merit,
            'damage' => $member->damage,
            'boss_kill' => $member->boss_kill,
            'gold_donated' => $member->gold_donated,
            'token_donated' => $member->token_donated,
            'role_limit_at' => $member->role_switch_cooldown ? $member->role_switch_cooldown->format('Y-m-d H:i:s') : '',
        ];

        Log::info('getCrewData: Returning data', ['crew_name' => $crew->name]);

        return (object)[
            'crew' => (object)$crewData,
            'char' => (object)$charData
        ];
    }
    
    public function createCrew($charId, $sessionKey, $name)
    {
        $character = Character::find($charId);
        if (!$character) {
            return (object)['errorMessage' => 'Character not found'];
        }
        
        if (Crew::where('name', $name)->exists()) {
            return (object)['errorMessage' => 'Crew name already taken'];
        }
        
        if (CrewMember::where('char_id', $charId)->exists()) {
            return (object)['errorMessage' => 'You are already in a crew'];
        }
        
        $user = User::find($character->user_id);
        $cost = (int)CrewSetting::getValue('cost_create_crew', 1000);
        
        if ($user->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }
        
        DB::beginTransaction();
        try {
            $user->tokens -= $cost;
            $user->save();
            
            $crew = Crew::create([
                'name' => $name,
                'master_id' => $charId,
                'max_members' => 20,
            ]);
            
            CrewMember::create([
                'crew_id' => $crew->id,
                'char_id' => $charId,
                'role' => self::ROLE_MASTER
            ]);
            
            DB::commit();
            return (object)['status' => 'ok'];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('createCrew error: ' . $e->getMessage());
            return (object)['errorMessage' => 'Failed to create crew'];
        }
    }

    public function quitFromCrew($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        if ($member->role == self::ROLE_MASTER) {
            // Disband crew
            CrewMember::where('crew_id', $member->crew_id)->delete();
            Crew::destroy($member->crew_id);
            return (object)['status' => 'ok'];
        } else {
            $member->delete();
            return (object)['status' => 'ok'];
        }
    }
    
    // --- Recruitment & Search ---

    public function getCrewsForRequest($charId, $sessionKey)
    {
        $crews = Crew::take(50)->get()->map(function($crew, $index) {
            return [
                'id' => $crew->id,
                'name' => $crew->name,
                'members' => $crew->members()->count(),
                'max_members' => $crew->max_members,
                'level' => $crew->level,
                'ranking' => $index + 1
            ];
        });
        
        return (object)['crews' => array_map(function($c) { return (object)$c; }, $crews->toArray())];
    }

    public function searchCrewsForRequest($charId, $sessionKey, $crewId)
    {
        $crew = Crew::find($crewId);
        if (!$crew) {
            return (object)['errorMessage' => 'Crew not found'];
        }
        
        return (object)[
            'crews' => [(object)[
                'id' => $crew->id,
                'name' => $crew->name,
                'members' => $crew->members()->count(),
                'max_members' => $crew->max_members,
                'level' => $crew->level,
                'ranking' => 1
            ]]
        ];
    }

    public function sendRequestToCrew($charId, $sessionKey, $crewId)
    {
        if (CrewMember::where('char_id', $charId)->exists()) {
            return (object)['errorMessage' => 'You are already in a crew'];
        }
        
        if (CrewRequest::where('char_id', $charId)->where('crew_id', $crewId)->exists()) {
            return (object)['errorMessage' => 'Request already sent'];
        }

        CrewRequest::create([
            'crew_id' => $crewId,
            'char_id' => $charId
        ]);

        return (object)['data' => (object)['result' => 'Request sent successfully']];
    }

    public function getMembersInfo($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        $members = CrewMember::where('crew_id', $member->crew_id)
            ->with('character')
            ->get()
            ->map(function($m) {
                return [
                    'char_id' => $m->char_id,
                    'name' => $m->character->name ?? 'Unknown',
                    'level' => $m->character->level ?? 1,
                    'role' => $m->role,
                    'stamina' => $m->stamina,
                    'damage' => $m->damage,
                    'boss_kill' => $m->boss_kill,
                    'gold_donated' => $m->gold_donated,
                    'token_donated' => $m->token_donated,
                ];
            });

        return (object)['members' => array_map(function($m) { return (object)$m; }, $members->toArray())];
    }
    
    public function getMemberRequests($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $requests = CrewRequest::where('crew_id', $member->crew_id)
            ->with('character')
            ->get()
            ->map(function($r) {
                return [
                    'char_id' => $r->char_id,
                    'name' => $r->character->name ?? 'Unknown',
                    'level' => $r->character->level ?? 1
                ];
            });
            
        return (object)['requests' => array_map(function($r) { return (object)$r; }, $requests->toArray())];
    }

    public function acceptMember($charId, $sessionKey, $targetCharId)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $request = CrewRequest::where('crew_id', $me->crew_id)->where('char_id', $targetCharId)->first();
        if (!$request) {
            return (object)['errorMessage' => 'Request not found'];
        }
        
        $currentCount = CrewMember::where('crew_id', $me->crew_id)->count();
        $crew = Crew::find($me->crew_id);
        
        if ($currentCount >= $crew->max_members) {
            return (object)['errorMessage' => 'Crew is full'];
        }
        
        DB::transaction(function() use ($request, $me, $targetCharId) {
            CrewMember::create([
                'crew_id' => $me->crew_id,
                'char_id' => $targetCharId,
                'role' => self::ROLE_MEMBER
            ]);
            $request->delete();
            CrewRequest::where('char_id', $targetCharId)->delete();
        });
        
        return (object)['result' => 'ok'];
    }

    public function rejectMember($charId, $sessionKey, $targetCharId)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        CrewRequest::where('crew_id', $me->crew_id)->where('char_id', $targetCharId)->delete();
        return (object)['result' => 'ok'];
    }

    public function rejectMembers($charId, $sessionKey)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        CrewRequest::where('crew_id', $me->crew_id)->delete();
        return (object)['result' => 'ok'];
    }

    // --- Donation & Upgrades ---

    public function donateGolds($charId, $sessionKey, $amount)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        $character = Character::find($charId);
        if ($character->gold < $amount) {
            return (object)['errorMessage' => 'Not enough gold'];
        }
        
        DB::transaction(function() use ($character, $member, $amount) {
            $character->gold -= $amount;
            $character->save();
            
            $crew = $member->crew;
            $crew->golds += $amount;
            $crew->save();
            
            $member->gold_donated += $amount;
            $member->contribution += intval($amount / 1000);
            $member->save();
        });
        
        return (object)[
            'g' => $character->fresh()->gold,
        ];
    }

    public function donateTokens($charId, $sessionKey, $amount)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        $character = Character::find($charId);
        $user = User::find($character->user_id);
        
        if ($user->tokens < $amount) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }
        
        DB::transaction(function() use ($user, $member, $amount) {
            $user->tokens -= $amount;
            $user->save();
            
            $crew = $member->crew;
            $crew->tokens += $amount;
            $crew->save();
            
            $member->token_donated += $amount;
            $member->save();
        });
        
        return (object)[
            't' => $user->fresh()->tokens
        ];
    }

    // --- Building & Management ---

    public function upgradeBuilding($charId, $sessionKey, $buildingId)
    {
        Log::info('upgradeBuilding called', [
            'char_id' => $charId,
            'building_id' => $buildingId
        ]);
        
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $crew = $member->crew;
        $buildingMap = [
            'KushiDangoBtn' => 'kushi_dango',
            'teahouseBtn' => 'tea_house',
            'bathhouseBtn' => 'bath_house',
            'trainingBtn' => 'training_centre',
            // Also support the upgrade IDs from gamedata.json
            'crew_kushi_dango' => 'kushi_dango',
            'crew_tea_house' => 'tea_house',
            'crew_bath_house' => 'bath_house',
            'crew_training_centre' => 'training_centre'
        ];
        
        $column = $buildingMap[$buildingId] ?? null;
        
        Log::info('Building mapping', [
            'building_id' => $buildingId,
            'mapped_column' => $column
        ]);
        
        if (!$column || !in_array($column, ['kushi_dango', 'tea_house', 'bath_house', 'training_centre'])) {
            return (object)['errorMessage' => 'Invalid building'];
        }
        
        $currentLevel = $crew->$column;
        
        Log::info('Current building level', [
            'building' => $column,
            'current_level' => $currentLevel,
            'crew_golds' => $crew->golds,
            'crew_tokens' => $crew->tokens
        ]);
        
        // Check if already max level (3)
        // Levels: 0 (not built), 1, 2, 3 (max)
        if ($currentLevel >= 3) {
            return (object)['errorMessage' => 'Building already at max level'];
        }
        
        // Get building data from gamedata.json
        $buildings = $this->getBuildingData();
        
        // Map database column to gamedata key
        $columnToKey = [
            'kushi_dango' => 'KushiDangoBtn',
            'tea_house' => 'teahouseBtn',
            'bath_house' => 'bathhouseBtn',
            'training_centre' => 'trainingBtn'
        ];
        
        $buildingKey = $columnToKey[$column] ?? null;
        
        Log::info('Building key lookup', [
            'column' => $column,
            'building_key' => $buildingKey,
            'available_keys' => array_keys($buildings)
        ]);
        
        if (!$buildingKey || !isset($buildings[$buildingKey])) {
            return (object)['errorMessage' => 'Building data not found'];
        }
        
        $buildingData = $buildings[$buildingKey];
        $nextLevel = $currentLevel + 1;
        
        // Get cost for next level
        $cost = $buildingData['price'][$nextLevel] ?? 0;
        
        Log::info('Upgrade cost', [
            'next_level' => $nextLevel,
            'cost' => $cost
        ]);
        
        // Determine currency type: levels 1-2 use gold, level 3+ use tokens
        if ($nextLevel <= 2) {
            // Use crew golds
            if ($crew->golds < $cost) {
                return (object)['errorMessage' => 'Not enough crew golds'];
            }
            $crew->golds -= $cost;
        } else {
            // Use crew tokens
            if ($crew->tokens < $cost) {
                return (object)['errorMessage' => 'Not enough crew tokens'];
            }
            $crew->tokens -= $cost;
        }
        
        $crew->$column = $nextLevel;
        $crew->save();
        
        Log::info('Building upgraded successfully', [
            'building' => $column,
            'new_level' => $nextLevel
        ]);
        
        return null; // AS3 expects null for success
    }

    public function increaseMaxMembers($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $crew = $member->crew;
        
        // Calculate cost and increase amount
        $increaseBy = min(10, 40 - $crew->max_members);
        if ($increaseBy <= 0) {
            return (object)['errorMessage' => 'Max limit reached'];
        }
        
        $newMax = $crew->max_members + $increaseBy;
        $multiplier = (int)CrewSetting::getValue('cost_increase_member_base', 10);
        $cost = $newMax * $multiplier; // Cost formula from AS3
        
        if ($crew->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }
        
        $crew->tokens -= $cost;
        $crew->max_members = $newMax;
        $crew->save();
        
        return (object)['max_members' => $crew->max_members];
    }

    public function updateAnnouncement($charId, $sessionKey, $text)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $member->crew->announcement = $text;
        $member->crew->save();
        
        return null; // AS3 expects null for success
    }

    public function renameCrew($charId, $sessionKey, $newName)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role != self::ROLE_MASTER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $cost = (int)CrewSetting::getValue('cost_rename_crew', 3000);
        $crew = $me->crew;
        
        if ($crew->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }
        
        if (Crew::where('name', $newName)->where('id', '!=', $crew->id)->exists()) {
            return (object)['errorMessage' => 'Name taken'];
        }
        
        $crew->tokens -= $cost;
        $crew->name = $newName;
        $crew->last_renamed_at = now();
        $crew->save();
        
        return null; // AS3 expects null for success
    }

    // --- Stamina System ---

    public function getStamina($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        // Apply passive stamina regeneration
        $this->applyPassiveStaminaRegen($member);
        
        // Get dynamic season data
        $seasonData = $this->getInternalSeasonData();

        return (object)[
            'char' => (object)[
                'stamina' => $member->stamina,
                'max_stamina' => $member->max_stamina,
                'char_id' => $member->char_id,
                'merit' => $member->merit,
                'role' => $member->role,
            ],
            'season' => (object)['phase' => $seasonData->phase, 'timestamp' => $seasonData->remaining]
        ];
    }

    /**
     * Apply passive stamina regeneration based on Training Centre level
     * Regenerates every 30 minutes:
     * - Level 0: 30 stamina
     * - Level 1: 40 stamina (30 + 10)
     * - Level 2: 50 stamina (30 + 20)
     * - Level 3: 60 stamina (30 + 30)
     */
    private function applyPassiveStaminaRegen($member)
    {
        $crew = $member->crew;
        
        // Check if last regen was recorded
        if (!$member->last_stamina_regen) {
            $member->last_stamina_regen = now();
            $member->save();
            return;
        }
        
        $lastRegen = \Carbon\Carbon::parse($member->last_stamina_regen);
        $now = now();
        $minutesPassed = $lastRegen->diffInMinutes($now);
        
        // Regenerate every 30 minutes
        if ($minutesPassed >= 30) {
            $regenCycles = floor($minutesPassed / 30);
            
            // Base stamina regen: 30
            // Training Centre bonus from gamedata: [0, 10, 20, 30]
            $baseRegen = 30;
            $trainingBonus = $this->getBuildingBonus('trainingBtn', $crew->training_centre);
            $staminaPerCycle = $baseRegen + $trainingBonus;
            
            $totalRegen = $staminaPerCycle * $regenCycles;
            
            Log::info('Passive stamina regeneration', [
                'char_id' => $member->char_id,
                'minutes_passed' => $minutesPassed,
                'regen_cycles' => $regenCycles,
                'training_level' => $crew->training_centre,
                'stamina_per_cycle' => $staminaPerCycle,
                'total_regen' => $totalRegen,
                'old_stamina' => $member->stamina
            ]);
            
            $member->stamina = min($member->max_stamina, $member->stamina + $totalRegen);
            $member->last_stamina_regen = $lastRegen->addMinutes($regenCycles * 30);
            $member->save();
            
            Log::info('Stamina regenerated', [
                'new_stamina' => $member->stamina,
                'next_regen' => $member->last_stamina_regen
            ]);
        }
    }

    public function restoreStamina($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        if ($member->stamina >= $member->max_stamina) {
            return (object)[
                'status' => 'ok',
                'data' => (object)[
                    'currency_type' => 0,
                    'restored_stamina' => 0,
                    'currency_used' => 0,
                    'currency_remaining' => 0
                ]
            ];
        }
        
        $character = Character::find($charId);
        $user = User::find($character->user_id);
        
        // Check for Golden Onigiri (material_941)
        $onigiri = \App\Models\CharacterItem::where('character_id', $charId)
            ->where('item_id', 'material_941')
            ->first();
        
        $restored = 50;
        $usedType = 1; // 1: Token, 2: Roll
        $cost = 0;
        $remaining = 0;
        
        if ($onigiri && $onigiri->quantity > 0) {
            // Use onigiri
            $usedType = 2;
            $cost = 1;
            $onigiri->quantity -= 1;
            if ($onigiri->quantity == 0) {
                $onigiri->delete();
            } else {
                $onigiri->save();
            }
            $remaining = $onigiri->quantity;
        } else {
            // Use tokens
            $cost = (int)CrewSetting::getValue('cost_stamina_restore_token', 10);
            if ($user->tokens < $cost) {
                return (object)['code' => 402, 'errorMessage' => 'Not enough tokens'];
            }
            $user->tokens -= $cost;
            $user->save();
            $remaining = $user->tokens;
        }
        
        $member->stamina = min($member->max_stamina, $member->stamina + $restored);
        $member->save();
        
        return (object)[
            'status' => 'ok',
            'data' => (object)[
                'currency_type' => $usedType,
                'restored_stamina' => $restored,
                'currency_used' => $cost,
                'currency_remaining' => $remaining
            ]
        ];
    }

    public function upgradeMaxStamina($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }
        
        $character = Character::find($charId);
        $user = User::find($character->user_id);
        $cost = 500;
        
        if ($user->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }
        
        if ($member->max_stamina >= 200) {
            return (object)['errorMessage' => 'Max stamina limit reached'];
        }
        
        $user->tokens -= $cost;
        $user->save();
        
        $member->max_stamina += 50;
        $member->save();
        
        return (object)['status' => 'ok'];
    }

    // --- Member Role Management ---

    public function kickMember($charId, $sessionKey, $targetId)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $target = CrewMember::where('char_id', $targetId)->where('crew_id', $me->crew_id)->first();
        if (!$target) {
            return (object)['errorMessage' => 'Member not found'];
        }
        
        if ($target->role <= $me->role) {
            return (object)['errorMessage' => 'Cannot kick equal or higher rank'];
        }
        
        $target->delete();
        return null; // AS3 expects null for success
    }

    public function promoteElder($charId, $sessionKey, $targetId)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role != self::ROLE_MASTER) {
            return (object)['errorMessage' => 'Only Master can promote Elder'];
        }
        
        $target = CrewMember::where('char_id', $targetId)->where('crew_id', $me->crew_id)->first();
        if (!$target) {
            return (object)['errorMessage' => 'Target not found'];
        }
        
        $target->role = self::ROLE_ELDER;
        $target->save();
        
        $me->crew->elder_id = $targetId;
        $me->crew->save();
        
        return null; // AS3 expects null for success
    }

    public function changeCrewMaster($charId, $sessionKey, $targetId)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role != self::ROLE_MASTER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        $target = CrewMember::where('char_id', $targetId)->where('crew_id', $me->crew_id)->first();
        if (!$target) {
            return (object)['errorMessage' => 'Member not found'];
        }
        
        DB::transaction(function() use ($me, $target) {
            $me->role = self::ROLE_MEMBER;
            $me->save();
            
            $target->role = self::ROLE_MASTER;
            $target->save();
            
            $crew = $me->crew;
            $crew->master_id = $target->char_id;
            // Clear elder_id if the new master was the elder
            if ($crew->elder_id == $target->char_id) {
                $crew->elder_id = 0;
            }
            $crew->save();
        });
        
        return null; // AS3 expects null for success
    }

    public function inviteCharacter($charId, $sessionKey, $targetId)
    {
        // Send invite to character (creates a request on their behalf)
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }
        
        // Check if target is already in a crew
        if (CrewMember::where('char_id', $targetId)->exists()) {
            return (object)['errorMessage' => 'Character already in a crew'];
        }
        
        return (object)['status' => 'ok'];
    }

    // --- History ---

    public function getHistory($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['histories' => []];
        }
        
        // Get from crew_history_logs table
        $logs = \App\Models\CrewHistoryLog::where('crew_id', $member->crew_id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->pluck('message')
            ->toArray();
        
        return (object)['histories' => $logs];
    }

    // --- Battle System ---

    public function startBattle($charId, $sessionKey, $phase, $data)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        // Validate hash to prevent cheating
        // data contains: b (random string), c (castle id), t (timestamp), h (hash), e (enemy for phase 1), f (friends)
        $expectedHash = md5(implode('|', [$charId, $data['b'] ?? '', $data['t'] ?? '', $data['c'] ?? '', $data['e'] ?? '']));
        if (!isset($data['h']) || strtolower($data['h']) !== strtolower($expectedHash)) {
            return (object)['errorMessage' => 'Invalid battle data'];
        }

        // Check timestamp (prevent replay attacks - within 1 hour)
        // Flash sends timestamp in milliseconds for phase 1, seconds for phase 2
        $timestamp = $data['t'] ?? 0;
        
        // If timestamp is in milliseconds (> 10 billion), convert to seconds
        if ($timestamp > 10000000000) {
            $timestamp = intval($timestamp / 1000);
        }
        
        $timeDiff = abs(time() - $timestamp);
        if ($timeDiff > 3600) { // 1 hour tolerance
            Log::warning('Battle timestamp validation failed', [
                'client_time' => $timestamp,
                'server_time' => time(),
                'diff_seconds' => $timeDiff
            ]);
            return (object)['errorMessage' => 'Battle request expired'];
        }

        if ($phase == 1) {
            // Phase 1: PvE Boss Battle
            // Check stamina requirement (10 stamina)
            if ($member->stamina < 10) {
                return (object)['code' => 402, 'errorMessage' => 'Not enough stamina'];
            }

            // Return battle initialization data
            return (object)[
                'c' => $data['c'] ?? 1, // mission/background id
                'f' => $data['f'] ?? [], // recruited friends
                'b' => $data['b'] // battle token
            ];
        } else {
            // Phase 2: Castle War
            $castleId = $data['c'] ?? null;
            if (!$castleId) {
                return (object)['errorMessage' => 'Invalid castle'];
            }

            $castle = Castle::find($castleId);
            if (!$castle) {
                return (object)['errorMessage' => 'Castle not found'];
            }

            // Check if crew already owns this castle
            if ($castle->owner_crew_id == $member->crew_id) {
                return (object)['code' => 406, 'errorMessage' => 'Your crew already owns this castle'];
            }

            // Check stamina requirement (10 stamina for phase 2)
            if ($member->stamina < 10) {
                return (object)['code' => 402, 'errorMessage' => 'Not enough stamina'];
            }

            // Store battle data for finishBattle
            // In real implementation, you'd store this in a battle_sessions table
            // For now, we'll process it immediately in finishBattle

            return (object)[
                'b' => $data['b'], // battle token
                'c' => $castleId
            ];
        }
    }

    public function finishBattle($charId, $sessionKey, $phase, $data)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        $crew = $member->crew;

        if ($phase == 1) {
            // Phase 1: Boss Battle Result
            // Validate battle data
            if (!isset($data['b'])) {
                return (object)['errorMessage' => 'Invalid battle result'];
            }

            // Check stamina
            if ($member->stamina < 10) {
                return (object)['code' => 402, 'errorMessage' => 'Not enough stamina'];
            }

            $won = true;
            
            // Damage is based on battle completion time
            // - Complete in < 5 minutes: 60 damage
            // - After 5 minutes: -1 damage per 10 seconds
            // - Minimum: 25 damage
            
            // The timestamp 't' in the data is when startBattle was called
            // We need to calculate how long ago that was
            $battleStartTime = $data['t'] ?? time();
            
            // If timestamp is in milliseconds (> 10 billion), convert to seconds
            if ($battleStartTime > 10000000000) {
                $battleStartTime = intval($battleStartTime / 1000);
            }
            
            // Calculate battle duration
            $currentTime = time();
            $battleDuration = $currentTime - $battleStartTime; // Duration in seconds
            
            // If duration is negative or unreasonably large, assume instant completion
            if ($battleDuration < 0 || $battleDuration > 3600) {
                Log::warning('Invalid battle duration detected', [
                    'start_time' => $battleStartTime,
                    'current_time' => $currentTime,
                    'duration' => $battleDuration
                ]);
                $battleDuration = 60; // Default to 1 minute
            }
            
            // Calculate damage based on time
            if ($battleDuration <= 300) {
                // Less than or equal to 5 minutes: max damage
                $damage = 60;
            } else {
                // After 5 minutes: deduct 1 damage per 10 seconds
                $secondsOver = $battleDuration - 300;
                $deduction = floor($secondsOver / 10);
                $damage = 60 - $deduction;
                
                // Minimum damage is 25
                $damage = max(25, $damage);
            }
            
            $character = Character::find($charId);
            
            Log::info('Crew battle damage calculation', [
                'battle_start_time' => $battleStartTime,
                'current_time' => $currentTime,
                'battle_duration' => $battleDuration,
                'damage' => $damage,
                'char_name' => $character->name
            ]);
            
            // Merit calculation
            $merit = 50;
            
            // Tea House increases merit gain
            $teaHouseBonus = ($crew->tea_house - 1) * 5; // +5 merit per level
            $merit += $teaHouseBonus;

            // Deduct stamina
            $member->stamina -= 10;
            
            Log::info('Before saving crew member stats', [
                'char_id' => $charId,
                'old_damage' => $member->getOriginal('damage'),
                'new_damage' => $member->damage,
                'damage_to_add' => $damage,
                'old_boss_kill' => $member->getOriginal('boss_kill'),
                'new_boss_kill' => $member->boss_kill,
                'old_merit' => $member->getOriginal('merit'),
                'new_merit' => $member->merit
            ]);
            
            // Add damage and merit
            $member->damage += $damage;
            $member->merit += $merit;
            if ($won) {
                $member->boss_kill += 1;
                
                // Track boss kills per castle
                $castleId = $data['c'] ?? 0;
                if ($castleId > 0) {
                    // Start castle IDs at 1 in DB same as input
                    // Check if record exists
                    $stat = DB::table('crew_castle_stats')
                        ->where('crew_id', $crew->id)
                        ->where('castle_id', $castleId)
                        ->first();
                        
                    if ($stat) {
                        DB::table('crew_castle_stats')
                            ->where('id', $stat->id)
                            ->increment('boss_kills');
                    } else {
                        DB::table('crew_castle_stats')->insert([
                            'crew_id' => $crew->id,
                            'castle_id' => $castleId,
                            'boss_kills' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
            $member->save();
            
            Log::info('After saving crew member stats', [
                'char_id' => $charId,
                'final_damage' => $member->damage,
                'final_boss_kill' => $member->boss_kill,
                'final_merit' => $member->merit,
                'final_stamina' => $member->stamina,
                'castle_id' => $data['c'] ?? 'unknown'
            ]);

            // Log to history
            $this->addHistory($crew->id, "{$character->name} defeated a boss and dealt {$damage} damage!");
            
            // Return data object - MissionReward.as expects: data.m (merit) and data.d (damage)
            return (object)[
                'data' => (object)[
                    'd' => $damage,  // Red explosion icon - damage
                    'm' => $merit,   // Blue diamond icon - merit
                    's' => $member->stamina
                ]
            ];
        } else {
            // Phase 2: Castle War Result
            $castleId = $data['c'] ?? null;
            if (!$castleId) {
                return (object)['errorMessage' => 'Invalid castle'];
            }

            $castle = Castle::lockForUpdate()->find($castleId);
            if (!$castle) {
                return (object)['errorMessage' => 'Castle not found'];
            }

            // Check if crew already owns this castle
            if ($castle->owner_crew_id == $member->crew_id) {
                return (object)['code' => 406, 'errorMessage' => 'Your crew already owns this castle'];
            }

            // Check stamina
            if ($member->stamina < 10) {
                return (object)['code' => 402, 'errorMessage' => 'Not enough stamina'];
            }

            // Calculate attack damage
            $baseDamage = 5; // Base 5% damage per attack
            
            // Building bonuses
            $trainingBonus = ($crew->training_centre - 1) * 2; // +2% per level
            $bathHouseBonus = ($crew->bath_house - 1) * 1; // +1% per level
            
            $damagePercent = $baseDamage + $trainingBonus + $bathHouseBonus;
            
            // Character level bonus
            $character = Character::find($charId);
            $levelBonus = floor($character->level / 20); // +1% per 20 levels
            $damagePercent += $levelBonus;

            // Random variance ±20%
            $variance = rand(80, 120) / 100;
            $damagePercent = round($damagePercent * $variance);

            // Determine which HP to attack (wall first, then defenders)
            $attackedWall = false;
            if ($castle->wall_hp > 0) {
                // Attack wall
                $castle->wall_hp = max(0, $castle->wall_hp - $damagePercent);
                $attackedWall = true;
            } else {
                // Attack defenders
                $castle->defender_hp = max(0, $castle->defender_hp - $damagePercent);
            }

            // Check if castle is conquered
            $conquered = false;
            $defenderCrew = null;
            if ($castle->wall_hp <= 0 && $castle->defender_hp <= 0) {
                // Castle conquered!
                $conquered = true;
                $oldOwnerId = $castle->owner_crew_id;
                $defenderCrew = $oldOwnerId ? Crew::find($oldOwnerId) : null;
                
                $castle->owner_crew_id = $crew->id;
                $castle->wall_hp = 100;
                $castle->defender_hp = 100;
                
                $this->addHistory($crew->id, "{$crew->name} conquered {$castle->name}!");
                if ($defenderCrew) {
                    $this->addHistory($defenderCrew->id, "{$castle->name} was lost to {$crew->name}!");
                }
            }

            $castle->save();

            // Deduct stamina and add stats
            $member->stamina -= 10;
            $member->damage += $damagePercent * 10; // Convert % to points for tracking
            
            // Merit calculation
            $merit = $conquered ? 100 : round($damagePercent * 2);
            $member->merit += $merit;
            $member->save();

            // Log attack
            $target = $attackedWall ? 'wall' : 'defenders';
            $this->addHistory($crew->id, "{$character->name} attacked {$castle->name}'s {$target} for {$damagePercent}% damage!");

            return (object)[
                'b' => 1, // battle completed
                'w' => $conquered ? $crew->name : ($defenderCrew ? $defenderCrew->name : 'Neutral'),
                'l' => $conquered ? ($defenderCrew ? $defenderCrew->name : 'Neutral') : $crew->name,
                'd' => round($damagePercent * 10), // Damage dealt (in points)
                'm' => $merit,
                's' => $member->stamina,
                'conquered' => $conquered
            ];
        }
    }

    private function addHistory($crewId, $message)
    {
        try {
            \App\Models\CrewHistoryLog::create([
                'crew_id' => $crewId,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add crew history: ' . $e->getMessage());
        }
    }

    public function getCastles($charId, $sessionKey, $castleId = null)
    {
        $castles = $castleId ? Castle::where('id', $castleId)->get() : Castle::all();
        
        if ($castles->isEmpty()) {
            // Create default castles
            $names = ['Northern Castle', 'Southern Castle', 'Eastern Castle', 'Western Castle', 'Central Castle', 'Mountain Castle', 'Valley Castle'];
            for ($i = 0; $i < 7; $i++) {
                Castle::create([
                    'name' => $names[$i] ?? 'Castle ' . ($i + 1),
                    'wall_hp' => 100,
                    'defender_hp' => 100
                ]);
            }
            $castles = $castleId ? Castle::where('id', $castleId)->get() : Castle::all();
        }
        
        $data = $castles->map(function($c) {
            $owner = $c->owner_crew_id ? Crew::find($c->owner_crew_id) : null;
            return [
                'id' => $c->id,
                'name' => $c->name,
                'owner_id' => $c->owner_crew_id,
                'owner_name' => $owner ? $owner->name : '',
                'wall_hp' => $c->wall_hp,
                'defender_hp' => $c->defender_hp
            ];
        });
        
        return (object)['castles' => array_map(function($c) { return (object)$c; }, $data->toArray()), 'a' => ''];
    }

    public function getDefenders($charId, $sessionKey, $castleId)
    {
        $castle = Castle::find($castleId);
        if (!$castle || !$castle->owner_crew_id) {
            return (object)['defenders' => []];
        }

        // Get defenders (crew members of the castle owner)
        $defenders = CrewMember::where('crew_id', $castle->owner_crew_id)
            ->with('character')
            ->get()
            ->map(function($m) {
                return [
                    'char_id' => $m->char_id,
                    'name' => (string)($m->character->name ?? 'Unknown'),
                    'name_color' => $m->character->name_color,
                    'level' => $m->character->level ?? 1,
                    'damage' => $m->damage
                ];
            });

        return (object)['defenders' => array_map(function($d) { return (object)$d; }, $defenders->toArray())];
    }
    
    public function getRecruits($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['members' => []];
        }

        // Return crew members available for recruitment in battle
        $members = CrewMember::where('crew_id', $member->crew_id)
            ->where('char_id', '!=', $charId)
            ->with('character')
            ->get()
            ->map(function($m) {
                $petData = (object)[];
                if ($m->character->equipped_pet_id) {
                    $pet = CharacterPet::where('character_id', $m->char_id)->find($m->character->equipped_pet_id);
                    if ($pet) {
                        $petData = (object)[
                            'pet_id' => $pet->id,
                            'pet_name' => $pet->pet_name,
                            'pet_level' => $pet->pet_level,
                            'pet_swf' => $pet->pet_swf,
                            'pet_skills' => $pet->pet_skills,
                            'pet_mp' => $pet->pet_mp,
                            'pet_xp' => $pet->pet_xp,
                        ];
                    }
                }

                return (object)[
                    'char_id' => (int)$m->char_id,
                    'name' => (string)($m->character->name ?? 'Unknown'),
                    'name_color' => $m->character->name_color,
                    'nickname' => (string)($m->character->name ?? 'Unknown'), // Alias for some clients
                    'level' => (int)($m->character->level ?? 1),
                    'stamina' => (int)$m->stamina,
                    'max_stamina' => (int)($m->max_stamina ?? 200),
                    'reputation' => (int)($m->character->reputation ?? 0),
                    'gender' => (int)($m->character->gender ?? 1),
                    'type' => (int)($m->character->type ?? 1),
                    'hair' => (int)($m->character->hair ?? 1),
                    'face' => (int)($m->character->face ?? 1),
                    'eye' => (int)($m->character->eye ?? 1),
                    'skin_color' => (int)($m->character->skin_color ?? 1),
                    'role' => (int)$m->role,
                    'pet_data' => $petData
                ];
            });

        return (object)['members' => $members->toArray()];
    }

    public function getAttackers($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['members' => []];
        }

        // Return crew members available for recruitment in battle
        $attackers = CrewMember::where('crew_id', $member->crew_id)
            ->where('char_id', '!=', $charId)
            ->with('character')
            ->get()
            ->map(function($m) {
                $petData = (object)[];
                if ($m->character->equipped_pet_id) {
                    $pet = CharacterPet::where('character_id', $m->char_id)->find($m->character->equipped_pet_id);
                    if ($pet) {
                        $petData = (object)[
                            'pet_id' => $pet->id,
                            'pet_name' => $pet->pet_name,
                            'pet_level' => $pet->pet_level,
                            'pet_swf' => $pet->pet_swf,
                            'pet_skills' => $pet->pet_skills,
                            'pet_mp' => $pet->pet_mp,
                            'pet_xp' => $pet->pet_xp,
                        ];
                    }
                }

                return (object)[
                    'char_id' => (int)$m->char_id,
                    'name' => (string)($m->character->name ?? 'Unknown'),
                    'name_color' => $m->character->name_color,
                    'nickname' => (string)($m->character->name ?? 'Unknown'), // Alias for some clients
                    'level' => (int)($m->character->level ?? 1),
                    'stamina' => (int)$m->stamina,
                    'max_stamina' => (int)($m->max_stamina ?? 200),
                    'reputation' => (int)($m->character->reputation ?? 0),
                    'gender' => (int)($m->character->gender ?? 1),
                    'type' => (int)($m->character->type ?? 1),
                    'hair' => (int)($m->character->hair ?? 1),
                    'face' => (int)($m->character->face ?? 1),
                    'eye' => (int)($m->character->eye ?? 1),
                    'skin_color' => (int)($m->character->skin_color ?? 1),
                    'role' => (int)$m->role,
                    'pet_data' => $petData
                ];
            });

        return (object)['members' => $attackers->toArray()];
    }

    public function switchRole($charId, $sessionKey, $roleId)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        // Check cooldown (24 hours)
        if ($member->role_switch_cooldown && $member->role_switch_cooldown->isFuture()) {
            return (object)['errorMessage' => 'Role switch is on cooldown'];
        }

        // Switch between attacker (role 1) and defender (role 2) for battles
        // This is different from crew role (master/elder/member)
        // Store in a separate field or use role_switch_cooldown to track

        $member->role_switch_cooldown = now()->addDay();
        $member->save();

        return (object)['status' => 'ok'];
    }

    public function getCrewRanks($charId, $sessionKey, $castleId)
    {
        Log::info('getCrewRanks called', [
            'char_id' => $charId,
            'castle_id' => $castleId
        ]);

        // Get all crews that have stats for this castle
        $stats = DB::table('crew_castle_stats')
            ->where('castle_id', $castleId)
            ->orderBy('boss_kills', 'desc')
            ->get();
            
        $crews = [];
        foreach ($stats as $stat) {
            $crew = Crew::find($stat->crew_id);
            if ($crew) {
                // Count total members
                $totalMembers = CrewMember::where('crew_id', $crew->id)->count();
                
                $crews[] = (object)[
                    'id' => $crew->id,
                    'name' => $crew->name,
                    'total_members' => $totalMembers,
                    'max_members' => $crew->max_members,
                    'boss_killed' => $stat->boss_kills
                ];
            }
        }

        Log::info('Crew rankings retrieved', [
            'total_crews' => count($crews),
            'castle_id' => $castleId
        ]);

        return (object)[
            'crews' => $crews,
            'total' => count($crews)
        ];
    }

    public function getRecoverLifeBar($charId, $sessionKey, $castleId)
    {
        $castle = Castle::find($castleId);
        if (!$castle) {
            return (object)['recovery' => []];
        }

        // Calculate recovery cost (tokens needed to restore HP)
        $wallRecoveryCost = (100 - $castle->wall_hp) * 10;
        $defenderRecoveryCost = (100 - $castle->defender_hp) * 10;

        return (object)[
            'recovery' => (object)[
                'wall_cost' => $wallRecoveryCost,
                'defender_cost' => $defenderRecoveryCost,
                'wall_hp' => $castle->wall_hp,
                'defender_hp' => $castle->defender_hp
            ]
        ];
    }

    public function recoverCastle($charId, $sessionKey, $castleId)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }

        $castle = Castle::find($castleId);
        if (!$castle || $castle->owner_crew_id != $member->crew_id) {
            return (object)['errorMessage' => 'You do not own this castle'];
        }

        $crew = $member->crew;
        
        // Calculate total cost
        $wallRecoveryCost = (100 - $castle->wall_hp) * 10;
        $defenderRecoveryCost = (100 - $castle->defender_hp) * 10;
        $totalCost = $wallRecoveryCost + $defenderRecoveryCost;

        if ($crew->tokens < $totalCost) {
            return (object)['errorMessage' => 'Not enough crew tokens'];
        }

        $crew->tokens -= $totalCost;
        $crew->save();

        $castle->wall_hp = 100;
        $castle->defender_hp = 100;
        $castle->save();

        return (object)['status' => 'ok'];
    }

    // --- Other Methods ---

    public function getCrewsForBattle($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        $myCrewId = $member ? $member->crew_id : null;
        
        // Return crews excluding player's own crew
        $crews = Crew::when($myCrewId, function($query) use ($myCrewId) {
                return $query->where('id', '!=', $myCrewId);
            })
            ->take(50)
            ->get()
            ->map(function($crew, $index) {
                $totalDamage = CrewMember::where('crew_id', $crew->id)->sum('damage');
                return [
                    'id' => $crew->id,
                    'name' => $crew->name,
                    'members' => $crew->members()->count(),
                    'max_members' => $crew->max_members,
                    'level' => $crew->level,
                    'damage' => $totalDamage,
                    'ranking' => $index + 1
                ];
            });
        
        return (object)['crews' => array_map(function($c) { return (object)$c; }, $crews->toArray())];
    }

    public function searchCrewsForBattle($charId, $sessionKey, $crewId)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        $myCrewId = $member ? $member->crew_id : null;
        
        $crew = Crew::find($crewId);
        if (!$crew) {
            return (object)['errorMessage' => 'Crew not found'];
        }
        
        if ($myCrewId && $crew->id == $myCrewId) {
            return (object)['errorMessage' => 'Cannot battle your own crew'];
        }
        
        $totalDamage = CrewMember::where('crew_id', $crew->id)->sum('damage');
        
        return (object)[
            'crews' => [(object)[
                'id' => $crew->id,
                'name' => $crew->name,
                'members' => $crew->members()->count(),
                'max_members' => $crew->max_members,
                'level' => $crew->level,
                'damage' => $totalDamage,
                'ranking' => 1
            ]]
        ];
    }


    // --- Onigiri System ---

    public function buyOnigiriPackage($charId, $sessionKey, $packageId)
    {
        $character = Character::find($charId);
        if (!$character) {
            return (object)['errorMessage' => 'Character not found'];
        }

        $user = User::find($character->user_id);
        
        // Package definitions: [qty, price]
        // From OnigiriPackages.as lines 101-102
        $packages = [
            0 => ['qty' => 100, 'price' => 975],   // Package 1
            1 => ['qty' => 200, 'price' => 1900],  // Package 2
            2 => ['qty' => 500, 'price' => 4625],  // Package 3
        ];

        if (!isset($packages[$packageId])) {
            return (object)['errorMessage' => 'Invalid package'];
        }

        $package = $packages[$packageId];
        
        if ($user->tokens < $package['price']) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }

        DB::transaction(function() use ($user, $character, $package) {
            $user->tokens -= $package['price'];
            $user->save();
            
            // Add material_69 (Onigiri) to character inventory
            $item = \App\Models\CharacterItem::where('character_id', $character->id)
                ->where('item_id', 'material_69')
                ->first();
                
            if ($item) {
                $item->quantity += $package['qty'];
                $item->save();
            } else {
                \App\Models\CharacterItem::create([
                    'character_id' => $character->id,
                    'item_id' => 'material_69',
                    'quantity' => $package['qty'],
                    'category' => 'material'
                ]);
            }
        });

        return (object)[
            'qty' => $package['qty'],
            'price' => $package['price']
        ];
    }

    public function getOnigiriInfo($charId, $sessionKey, $memberId)
    {
        // Check how many onigiri the member has received (limit 40,000)
        // From ClanHall.as line 763: max_amount = 40000 - int(param1.onigiri)
        
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }

        // Get target member's received onigiri count
        // This would be tracked in a separate table or field
        // For now, return a placeholder that allows gifting
        $receivedOnigiri = 0; // TODO: Track this in crew_member_gifts table
        
        $remaining = 40000 - $receivedOnigiri;
        
        return (object)[
            'info' => "Can receive {$remaining} more onigiri",
            'onigiri' => $receivedOnigiri
        ];
    }

    public function giveOnigiri($charId, $sessionKey, $memberId, $amount)
    {
        $me = CrewMember::where('char_id', $charId)->first();
        if (!$me || $me->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }

        $character = Character::find($charId);
        $user = User::find($character->user_id);
        
        // Check if user has permanent emblem (from ClanHall.as line 808)
        if ($user->account_type == 0 && ($user->emblem_duration ?? -1) == -1) {
            return (object)['errorMessage' => 'Must be Permanent Emblem User!'];
        }

        // Price calculation: 1 onigiri = 1 token + 10% tax
        // From ClanHall.as lines 768-770
        $price = 1; // per onigiri
        $cost = $price * $amount;
        $tax = $cost * 0.1;
        $total = $cost + $tax;

        if ($user->tokens < $total) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }

        // Check recipient's limit
        $receivedOnigiri = 0; // TODO: Get from tracking table
        if ($receivedOnigiri + $amount > 40000) {
            return (object)['errorMessage' => 'Recipient has reached onigiri limit'];
        }

        DB::transaction(function() use ($user, $memberId, $amount, $total) {
            $user->tokens -= $total;
            $user->save();
            
            // Add onigiri to recipient's inventory
            $targetChar = Character::find($memberId);
            if ($targetChar) {
                $item = \App\Models\CharacterItem::where('character_id', $memberId)
                    ->where('item_id', 'material_69')
                    ->first();
                    
                if ($item) {
                    $item->quantity += $amount;
                    $item->save();
                } else {
                    \App\Models\CharacterItem::create([
                        'character_id' => $memberId,
                        'item_id' => 'material_69',
                        'quantity' => $amount,
                        'category' => 'material'
                    ]);
                }
            }
            
            // TODO: Track in crew_member_gifts table
        });

        $newReceived = $receivedOnigiri + $amount;
        $remaining = 40000 - $newReceived;

        return (object)[
            'info' => "Can receive {$remaining} more onigiri",
            'amount' => $amount,
            'price' => $total
        ];
    }

    // --- Prestige Boost ---

    public function boostPrestige($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        $character = Character::find($charId);
        $user = User::find($character->user_id);
        
        // Prestige boost costs tokens and gives temporary prestige multiplier
        // Duration: 24 hours, Cost: 500 tokens (example)
        $cost = 500;
        
        if ($user->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }

        // Check if already has active boost
        // This would be tracked in character table or separate boosts table
        $currentBoost = $character->prestige_boost_until ?? null;
        if ($currentBoost && Carbon::parse($currentBoost)->isFuture()) {
            return (object)['errorMessage' => 'Prestige boost is already active'];
        }

        $user->tokens -= $cost;
        $user->save();
        
        // Set boost expiration (24 hours from now)
        $character->prestige_boost_until = now()->addDay();
        $character->save();

        return (object)[
            'tokens' => $user->tokens,
            'prestige_boost' => $character->prestige_boost_until->timestamp,
            'result' => 'Prestige boost activated for 24 hours!'
        ];
    }

    // --- Announcement Publishing ---

    public function publishAnnouncement($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member || $member->role > self::ROLE_ELDER) {
            return (object)['errorMessage' => 'Unauthorized'];
        }

        $crew = $member->crew;
        
        if (!$crew->announcement || trim($crew->announcement) == '') {
            return (object)['errorMessage' => 'No announcement to publish'];
        }

        // Send announcement to all crew members' mailboxes
        // This would integrate with the mail system
        $members = CrewMember::where('crew_id', $crew->id)->get();
        
        foreach ($members as $crewMember) {
            // TODO: Create mail entry for each member
            // For now, just log it
            Log::info("Publishing announcement to member {$crewMember->char_id}: {$crew->announcement}");
        }

        return 'ok'; // AS3 expects string 'ok' for this endpoint (line 578)
    }

    // --- Season & Rewards (Placeholders for future implementation) ---

    public function seasonHistories($charId, $sessionKey)
    {
        // Return past season results
        // TODO: Query crew_season_rankings table
        return (object)['histories' => []];
    }

    public function getLastSeasonRewards($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['rewards' => []];
        }

        // TODO: Get rewards from last season based on crew ranking
        // Check crew_season_rankings for previous season
        return (object)['rewards' => []];
    }
    
    // --- Mini-game ---

    public function getMiniGame($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['energy' => 0];
        }
        
        // Reset attempts daily (max 3 per day)
        $lastRefill = $member->last_mini_game_energy_refill;
        
        // Check if last refill was on a different day (or never happened)
        if (!$lastRefill || Carbon::parse($lastRefill)->startOfDay()->lt(Carbon::now()->startOfDay())) {
            $member->mini_game_energy = 3; // Daily limit 3
            $member->last_mini_game_energy_refill = now();
            $member->save();
        }
        
        return (object)['energy' => $member->mini_game_energy];
    }

    public function startMiniGame($charId, $sessionKey)
    {
        $member = CrewMember::where('char_id', $charId)->first();
        
        // Reset attempts daily (max 3 per day) - safety check
        $lastRefill = $member->last_mini_game_energy_refill;
        if (!$lastRefill || Carbon::parse($lastRefill)->startOfDay()->lt(Carbon::now()->startOfDay())) {
            $member->mini_game_energy = 3; // Daily limit 3
            $member->last_mini_game_energy_refill = now();
            $member->save();
        }

        if (!$member || $member->mini_game_energy < 1) {
            return (object)['errorMessage' => 'Not enough energy'];
        }

        // Generate challenge token
        $challengeToken = bin2hex(random_bytes(16));
        
        return (object)[
            'c' => $challengeToken,
            't' => time()
        ];
    }

    public function finishMiniGame($charId, $sessionKey, $data)
    {
        Log::info('finishMiniGame called', [
            'charId' => $charId,
            'data' => $data
        ]);

        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        // Validate hash (skipped for now)
        
        // Deduct energy
        Log::info('Energy before deduct', ['energy' => $member->mini_game_energy]);
        
        if ($member->mini_game_energy > 0) {
            $member->mini_game_energy -= 1;
            $member->save();
        } else {
            Log::warning('Not enough energy to finish minigame', ['charId' => $charId]);
            return (object)['errorMessage' => 'Not enough energy'];
        }

        if (isset($data['r']) && $data['r'] == 1) {
            // Fetch rewards dynamically from database
            $potentialRewards = CrewMinigameReward::all();
            $rewards = [];

            foreach ($potentialRewards as $reward) {
                // Simple probability check
                if (rand(1, 100) <= $reward->probability) {
                    $rewards[] = $reward->item_id;
                }
            }

            // Fallback if no rewards triggered (ensure at least one if successful)
            if (empty($rewards) && $potentialRewards->isNotEmpty()) {
                $rewards[] = $potentialRewards->random()->item_id;
            } else if (empty($rewards)) {
                // Hard fallback if DB is empty
                $rewards = ['material_939', 'material_941']; 
            }

            
            $character = Character::find($charId);
            
            foreach ($rewards as $reward) {
                if (strpos($reward, 'gold_') === 0) {
                    $amount = (int) substr($reward, 5);
                    $character->gold += $amount;
                } elseif (strpos($reward, 'xp_') === 0) {
                    $amount = (int) substr($reward, 3);
                    $character->xp += $amount;
                } elseif (strpos($reward, 'material_') === 0) {
                    $itemId = $reward;
                    $item = \App\Models\CharacterItem::firstOrNew([
                        'character_id' => $charId,
                        'item_id' => $itemId
                    ]);
                    $item->quantity = ($item->quantity ?? 0) + 1;
                    $item->category = 'material';
                    $item->save();
                }
            }
            $character->save();
            
            Log::info('Rewards given', ['rewards' => $rewards]);
            return (object)['r' => $rewards];
        }

        // Lost
        Log::info('Minigame lost or invalid result', ['r' => $data['r'] ?? 'null']);
        return (object)['r' => []]; // Empty rewards = fail
    }

    public function buyMiniGame($charId, $sessionKey, $type)
    {
        $character = Character::find($charId);
        $user = User::find($character->user_id);
        
        // Buy additional mini-game energy
        $cost = 50; // tokens per energy
        
        if ($user->tokens < $cost) {
            return (object)['errorMessage' => 'Not enough tokens'];
        }

        $member = CrewMember::where('char_id', $charId)->first();
        if (!$member) {
            return (object)['errorMessage' => 'Not in a crew'];
        }

        if ($member->mini_game_energy >= 5) {
            return (object)['errorMessage' => 'Energy is already full'];
        }

        $user->tokens -= $cost;
        $user->save();
        
        $member->mini_game_energy = min(5, $member->mini_game_energy + 1);
        $member->save();

        return (object)['t' => $user->tokens];
    }

    // --- Helper Methods ---

    /**
     * Get building data from game data
     * Returns building information including upgrade costs and bonuses
     */
    private function getBuildingData()
    {
        $gameData = GameDataHelper::get_gamedata();
        
        if (empty($gameData)) {
            Log::error('GameDataHelper returned empty gamedata');
            return [];
        }
        
        // gamedata.json is an array of objects, find the one with id="crew"
        $crewData = null;
        foreach ($gameData as $item) {
            if (isset($item['id']) && $item['id'] === 'crew') {
                $crewData = $item['data'];
                break;
            }
        }
        
        if (!$crewData) {
            Log::error('Crew data not found in gamedata.json');
            return [];
        }
        
        $buildings = $crewData['building'] ?? [];
        
        Log::info('Building data loaded', [
            'building_count' => count($buildings),
            'building_keys' => array_keys($buildings)
        ]);
        
        return $buildings;
    }

    /**
     * Get castle names from game data
     * Returns array of castle names
     */
    private function getCastleNames()
    {
        $fallback = [
            'Hiroshima Castle',
            'Himeji Castle',
            'Kumamoto Castle',
            'Okazaki Castle',
            'Inuyama Castle',
            'Gifu Castle',
            'Hikone Castle'
        ];

        $gameData = GameDataHelper::get_gamedata();
        
        if (empty($gameData)) {
            return $fallback;
        }
        
        // gamedata.json is an array of objects, find the one with id="crew"
        $crewData = null;
        foreach ($gameData as $item) {
            if (isset($item['id']) && $item['id'] === 'crew') {
                $crewData = $item['data'];
                break;
            }
        }
        
        return $crewData['castle'] ?? [
            'Hiroshima Castle',
            'Himeji Castle',
            'Kumamoto Castle',
            'Okazaki Castle',
            'Inuyama Castle',
            'Gifu Castle',
            'Hikone Castle'
        ];
    }

    /**
     * Get building bonus amount by building type and level
     * @param string $buildingKey - e.g. 'teahouseBtn', 'trainingBtn', 'bathhouseBtn', 'KushiDangoBtn'
     * @param int $level - Building level (1-4)
     * @return int - Bonus amount
     */
    private function getBuildingBonus($buildingKey, $level)
    {
        $buildings = $this->getBuildingData();
        
        if (!isset($buildings[$buildingKey])) {
            return 0;
        }

        $amounts = $buildings[$buildingKey]['amount'] ?? [0];
        return $amounts[$level] ?? 0;
    }

    /**
     * Get castle name by index
     * @param int $index - Castle index (0-6)
     * @return string - Castle name
     */
    public function getCastleName($index)
    {
        $castles = $this->getCastleNames();
        return $castles[$index] ?? "Castle {$index}";
    }
}

