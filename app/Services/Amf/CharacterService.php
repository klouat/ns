<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterPet;
use App\Models\CharacterSkill;
use App\Models\CharacterTalentSkill;
use App\Models\CharacterGearPreset;
use App\Models\User;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CharacterService
{
    public function characterRegister($params)
    {
        $accountId = $params[0];
        $sessionKeyHash = $params[1];
        $charName = $params[2];
        $gender = $params[3];
        $element = $params[4];
        $hairColor = $params[5];
        $hairNum = $params[6];

        if (strlen($charName) < 2) {
            return (object)['status' => 2, 'error' => 'Character name too short.'];
        }


        try {
            return DB::transaction(function () use ($accountId, $charName, $gender, $element, $hairColor, $hairNum) {
                $char = new Character();
                $char->user_id = $accountId;
                $char->name = $charName;
                $char->gender = $gender;
                $char->element_1 = $element;
                $char->hair_style = $hairNum;
                $char->hair_color = $hairColor;
                $char->skin_color = 'null|null';
                $char->point_free = 1;
                
                // Determine Equipment IDs
                $genderSuffix = ($gender == 1) ? '_1' : '_0';
                $hairId = 'hair_' . str_pad($hairNum, 2, '0', STR_PAD_LEFT) . $genderSuffix;
                $setId = 'set_01' . $genderSuffix;
                $wpnId = 'wpn_01';
                $backId = 'back_01';
                
                // Equip Items
                $char->equipment_weapon = $wpnId;
                $char->equipment_clothing = $setId;
                $char->equipment_back = $backId;
                
                // Determine Starting Skill
                $startSkill = match ((int)$element) {
                    1 => 'skill_13', // Wind
                    2 => 'skill_10', // Fire
                    3 => 'skill_01', // Thunder
                    4 => 'skill_07', // Earth
                    5 => 'skill_09', // Water
                    default => 'skill_01'
                };
                
                $char->equipment_skills = $startSkill;
                $char->save();
                
                // Add Items to Inventory
                \App\Helpers\ItemHelper::addItem($char->id, $wpnId);
                \App\Helpers\ItemHelper::addItem($char->id, $setId);
                \App\Helpers\ItemHelper::addItem($char->id, $hairId);
                \App\Helpers\ItemHelper::addItem($char->id, $backId);
                
                // Add Skill to Learned List
                CharacterSkill::create([
                    'character_id' => $char->id,
                    'skill_id' => $startSkill
                ]);

                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("characterRegister error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function equipSet($charId, $sessionKey, $weapon, $backItem, $clothing, $accessory, $hair, $hairColor, $skinColor)
    {
        try {
            $char = Character::find($charId);
            if ($char == null) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            if (!$this->validateSession($char->user_id, $sessionKey)) {
                return (object)['status' => 0, 'error' => 'Session expired!'];
            }

            $char->equipment_weapon = $weapon;
            $char->equipment_back = $backItem;
            $char->equipment_clothing = $clothing;
            $char->equipment_accessory = $accessory;
            $char->hair_style = $hair;
            $char->hair_color = $hairColor;
            $char->skin_color = $skinColor;
            $char->save();

            return (object)['status' => 1];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function setPoints($charId, $sessionKey, $wind, $fire, $lightning, $water, $earth, $free)
    {
        try {
            $char = Character::find($charId);
            if ($char == null) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            if (!$this->validateSession($char->user_id, $sessionKey)) {
                return (object)['status' => 0, 'error' => 'Session expired!'];
            }

            $char->point_wind = $wind;
            $char->point_fire = $fire;
            $char->point_lightning = $lightning;
            $char->point_water = $water;
            $char->point_earth = $earth;
            $char->point_free = $free;
            $char->save();

            return (object)['status' => 1, 'error' => 0];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function equipSkillSet($charId, $sessionKey, $skillString)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

            if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

            // Ensure we store with commas, just in case client sends pipes
            $skillString = str_replace('|', ',', $skillString);

            $char->update([
                'equipment_skills' => $skillString
            ]);

            // Also update the first preset (index 1 or ID found first) if the user expects it to be synced
            $preset = \App\Models\CharacterSkillSet::where('character_id', $charId)
                        ->orderBy('preset_index', 'asc')
                        ->first();
            
            if ($preset) {
                $preset->skills = $skillString;
                $preset->save();
            } else {
                 // Create default preset if it doesn't exist
                 \App\Models\CharacterSkillSet::create([
                     'character_id' => $charId,
                     'preset_index' => 1,
                     'skills' => $skillString
                 ]);
            }

            return (object)['status' => 1, 'error' => 0];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getSkillSets($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

        $skillsets = \App\Models\CharacterSkillSet::where('character_id', $charId)
            ->orderBy('preset_index')
            ->get();

        $data = [];
        foreach ($skillsets as $s) {
            $data[] = (object)[
                'id' => $s->id,
                'skills' => $s->skills ?: ""
            ];
        }

        return (object)[
            'status' => 1,
            'skillsets' => $data
        ];
    }

    public function createSkillSet($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                $count = \App\Models\CharacterSkillSet::where('character_id', $charId)->count();
                
                $cost = ($user->account_type == 1) ? 200 : 600;
                if ($count == 0) {
                    $cost = 0;
                }

                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                $user->tokens -= $cost;
                $user->save();

                \App\Models\CharacterSkillSet::create([
                    'character_id' => $charId,
                    'preset_index' => $count + 1,
                    'skills' => $char->equipment_skills ?: ""
                ]);

                return $this->getSkillSets($charId, $sessionKey);
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function saveSkillSet($charId, $sessionKey, $presetId, $skills)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

        $preset = \App\Models\CharacterSkillSet::where('id', $presetId)
            ->where('character_id', $charId)
            ->first();

        if (!$preset) return (object)['status' => 2, 'result' => 'Preset not found!'];

        $preset->skills = $skills;
        $preset->save();

        $char->equipment_skills = $skills;
        $char->save();

        return $this->getSkillSets($charId, $sessionKey);
    }

    public function getInfo($charId, $sessionKey, $targetId, $type = null)
    {
        // Validate session for the requester
        $requester = Character::find($charId);
        if (!$requester) {
            return (object)['status' => 0, 'error' => 'Requester character not found'];
        }
        
        if (!$this->validateSession($requester->user_id, $sessionKey)) {
             return (object)['status' => 0, 'error' => 'Session expired!'];
        }

        // Handle 'char_' prefix often sent by the client
        if (str_starts_with($targetId, 'char_')) {
            $targetId = str_replace('char_', '', $targetId);
        }

        $char = Character::with('user')->find($targetId);

        if ($char == null) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $isFriend = \App\Models\Friend::where('character_id', $charId)
            ->where('friend_id', $targetId)
            ->where('status', 1)
            ->exists();

        $genderSuffix = '_0';
        if ($char->gender == 1) {
            $genderSuffix = '_1';
        }

        if (is_numeric($char->hair_style)) {
            $hairstyle = 'hair_' . str_pad($char->hair_style, 2, '0', STR_PAD_LEFT) . $genderSuffix;
        } else {
            $hairstyle = $char->hair_style;
            if ($hairstyle == null) {
                $hairstyle = 'hair_01' . $genderSuffix;
            }
        }

        $element2 = $char->element_2;
        if ($element2 == null) {
            $element2 = 0;
        }

        $element3 = $char->element_3;
        if ($element3 == null) {
            $element3 = 0;
        }

        $weapon = $char->equipment_weapon;
        if ($weapon == null) {
            $weapon = 'wpn_01';
        }

        $backItem = $char->equipment_back;
        if ($backItem == null) {
            $backItem = 'back_01';
        }

        $accessory = $char->equipment_accessory;
        if ($accessory == null) {
            $accessory = 'accessory_01';
        }

        $clothing = $char->equipment_clothing;
        if ($clothing == null) {
            $clothing = 'set_01' . $genderSuffix;
        }

        $skills = $char->equipment_skills;
        if ($skills == null) {
            $skills = 'skill_01';
        }

        $hairColor = $char->hair_color;
        if ($hairColor == null) {
            $hairColor = '0|0';
        }

        $skinColor = $char->skin_color;
        if ($skinColor == null) {
            $skinColor = '0|0';
        }

        $colorHex = strtoupper($char->name_color ?? '#FFFFFF');
        
        if (!str_starts_with($colorHex, '#')) {
            $colorHex = '#' . $colorHex;
        }

        return (object)[
            'status' => 1,
            'error' => 0,
            'friend' => $isFriend,
            'account_type' => $char->user ? $char->user->account_type : 0,
            'emblem_duration' => $char->user ? $char->user->emblem_duration : -1,
            'events' => [],
            'rgb_data' => [
                [
                    'id' => (string)$char->id,
                    'data' => $colorHex
                ]
            ],
            'character_data' => (object)[
                'character_id' => $char->id,
                'character_name' => $char->name,
                'character_name_color' => $char->name_color,
                'character_level' => $char->level,
                'character_xp' => $char->xp,
                'character_gender' => $char->gender,
                'character_rank' => match($char->rank) {
                    'Chunin' => 2,
                    'Tensai Chunin' => 3,
                    'Jounin' => 4,
                    'Tensai Jounin' => 5,
                    'Special Jounin' => 6,
                    'Tensai Special Jounin' => 7,
                    'Ninja Tutor' => 8,
                    'Senior Ninja Tutor' => 9,
                    'Sage' => 10,
                    default => 1
                },
                'character_prestige' => $char->prestige,
                'character_element_1' => $char->element_1,
                'character_element_2' => $element2,
                'character_element_3' => $element3,
                'character_talent_1' => $char->talent_1,
                'character_talent_2' => $char->talent_2,
                'character_talent_3' => $char->talent_3,
                'character_gold' => $char->gold,
                'character_tp' => $char->tp,
                'character_ss' => $char->character_ss,
                'character_class' => $char->class,
                'character_senjutsu' => $char->senjutsu ? strtolower($char->senjutsu) : null,
                'character_pet' => $char->equipped_pet_id ? (\App\Models\CharacterPet::find($char->equipped_pet_id)->pet_swf ?? null) : null,
                'character_pet_id' => $char->equipped_pet_id ? (int)$char->equipped_pet_id : 0,
                'character_pvp_points' => 0,
                'welcome_status' => (function() use ($char) {
                    $welcome = \App\Models\CharacterWelcomeLogin::where('character_id', $char->id)->first();
                    if (!$welcome) return 0;
                    $claimed = $welcome->claimed_days ?? [];
                    return count($claimed) >= 7 ? 1 : 0;
                })()
            ],
            'character_points' => (object)[
                'atrrib_wind' => $char->point_wind,
                'atrrib_fire' => $char->point_fire,
                'atrrib_lightning' => $char->point_lightning,
                'atrrib_water' => $char->point_water,
                'atrrib_earth' => $char->point_earth,
                'atrrib_free' => $char->point_free
            ],
            'character_slots' => (object)[
                'weapons' => 100,
                'back_items' => 100,
                'accessories' => 100,
                'hairstyles' => 100,
                'clothing' => 100
            ],
            'character_sets' => (object)[
                'weapon' => $weapon,
                'back_item' => $backItem,
                'accessory' => $accessory,
                'hairstyle' => $hairstyle,
                'clothing' => $clothing,
                'clothing' => $clothing,
                'skills' => $skills,
                'senjutsu_skills' => $char->equipped_senjutsu_skills,
                'hair_color' => $hairColor,
                'skin_color' => $skinColor,
                'face' => 'face_01' . $genderSuffix,
                'pet' => $char->equipped_pet_id ? (\App\Models\CharacterPet::find($char->equipped_pet_id)->pet_swf ?? null) : null,
                'character_pet_id' => $char->equipped_pet_id ? (int)$char->equipped_pet_id : 0, // Add here too
                'anims' => $char->equipped_animations ? (object)json_decode($char->equipped_animations, true) : (object)[]
            ],
            'character_inventory' => (object)[
                'char_weapons' => $this->getInventoryString($char, 'weapon'),
                'char_back_items' => $this->getInventoryString($char, 'back'),
                'char_accessories' => $this->getInventoryString($char, 'accessory'),
                'char_sets' => $this->getInventoryString($char, 'set'),
                'char_hairs' => $this->getCodeString($char, 'hair'), 
                'char_skills' => $this->getSkillsString($char),
                'char_talent_skills' => $this->getTalentSkillsString($char),
                'char_senjutsu_skills' => $this->getSenjutsuSkillsString($char),
                'char_materials' => $this->getInventoryString($char, 'material'),
                'char_items' => $this->getInventoryString($char, 'item'),
                'char_essentials' => $this->getInventoryString($char, 'essential'),
                'char_animations' => $this->getCodeString($char, 'animation')
            ],
            'features' => [],
            'recruiters' => (function() use ($char, $charId) {
                // Return who THIS character has recruited (for team display)
                $recruits = $char->recruits ?? [];
                
                \Illuminate\Support\Facades\Log::info('getInfo recruiters', [
                    'char_id' => $charId,
                    'recruits_from_db' => $recruits,
                    'recruits_type' => gettype($recruits)
                ]);
                
                if (empty($recruits)) {
                    return [];
                }
                
                $friendIds = array_map(function($id) {
                    return (object)['recruited_char_id' => str_starts_with($id, 'npc_') ? $id : 'char_' . $id];
                }, $recruits);
                
                $hash = hash('sha256', (string)$friendIds[0]->recruited_char_id);
                return [$friendIds, $hash];
            })(),
            'recruit_data' => array_map(function($id) {
                $c = Character::with('user')->find($id);
                return $c ? app(FriendService::class)->formatFriendData($c) : null;
            }, array_filter($char->recruits ?? [])),
            'pet_data' => $this->getEquippedPetData($char),
            'clan' => null
        ];
    }

    public function getMissionRoomData($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            $recruitIds = $char->recruits ?? [];
            $recruitData = [];
            
            foreach ($recruitIds as $id) {
                if (str_starts_with($id, 'npc_')) {
                    // It's an NPC
                    $npcData = $this->getNpcData($id);
                    if ($npcData) {
                        $recruitData[] = (object)[
                            'id' => (object)['recruiter_id' => $id], // ActionScript expects id.recruiter_id for NPCs
                            'type' => 'npc', 
                            'info' => (object)$npcData
                        ];
                    }
                } else {
                    // It's a friend/player
                    $friendChar = Character::with('user')->find($id);
                    if ($friendChar) {
                        $formatted = app(FriendService::class)->formatFriendData($friendChar);
                        $recruitData[] = (object)[
                            'id' => $id,
                            'type' => 'char',
                            'info' => $formatted
                        ];
                    }
                }
            }

            return (object)[
                'status' => 1,
                'error' => 0,
                'recruit' => $recruitData,
                'daily' => []
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("getMissionRoomData error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function getNpcData($npcId)
    {
        // Minimal data needed for display (Ryuma)
        $sets = [
             'weapon' => 'wpn_01',
             'clothing' => 'set_01_1',
             'hairstyle' => 'hair_01_1',
             'hair_color' => '0|0',
             'face' => 'face_01_1',
             'back_item' => 'back_01', 
             'accessory' => 'accessory_01',
             'skin_color' => '0|0',
             'skills' => '',
             'anims' => []
        ];
        
        $points = [
            'atrrib_wind' => 0,
            'atrrib_fire' => 0,
            'atrrib_lightning' => 0,
            'atrrib_water' => 0,
            'atrrib_earth' => 0,
            'atrrib_free' => 0
        ];
        
        $name = 'NPC';
        $rank = 1;
        
        if ($npcId == 'npc_3') {
             $name = 'Ryuma';
             $rank = 3;
             $sets['weapon'] = 'wpn_02';
             $sets['clothing'] = 'set_03_1';
             $sets['hairstyle'] = 'hair_03_1';
             $points['atrrib_wind'] = 20;
        } else if ($npcId == 'npc_4') {
             $name = 'Gekko';
             $rank = 3;
             $sets['weapon'] = 'wpn_03';
             $sets['clothing'] = 'set_04_1';
             $sets['hairstyle'] = 'hair_04_1';
             $points['atrrib_fire'] = 20;
        }

        return (object)[
            'character_id' => $npcId,
            'character_name' => $name,
            'character_level' => 20,
            'character_gender' => 1, // Male
            'character_rank' => $rank,
            'character_sets' => (object)$sets,
            'character_points' => (object)$points,
            'character_class' => null,
            'character_prestige' => 0,
            'clan' => null
        ];
    }

    public function buySkill($sessionKey, $charId, $skillId)
    {
        try {
            return DB::transaction(function () use ($charId, $skillId, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if (CharacterSkill::where('character_id', $charId)->where('skill_id', $skillId)->exists()) {
                    return (object)['status' => 2];
                }

                $skillData = GameDataHelper::find_skill($skillId);
                if (!$skillData) return (object)['status' => 0, 'error' => 'Skill data not found!'];
                $skill = (object)$skillData;

                if ($skill->premium && $user->account_type == 0) {
                    return (object)['status' => 6];
                }

                if ($char->level < $skill->level) return (object)['status' => 5];

                if ($skill->element >= 1 && $skill->element <= 5) {
                    $myElements = array_filter([$char->element_1, $char->element_2, $char->element_3]);
                    if (!in_array($skill->element, $myElements)) {
                        $maxElements = ($user->account_type >= 1) ? 3 : 2;
                        if (count($myElements) < $maxElements) {
                            if (!$char->element_1) {
                                $char->element_1 = $skill->element;
                            } elseif (!$char->element_2) {
                                $char->element_2 = $skill->element;
                            } elseif (!$char->element_3) {
                                $char->element_3 = $skill->element;
                            }
                            $char->save();
                        } else {
                            return (object)['status' => 4];
                        }
                    }
                }

                if ($char->gold < $skill->price_gold || $user->tokens < $skill->price_tokens) return (object)['status' => 3];

                $char->gold -= $skill->price_gold;
                $char->save();

                if ($skill->price_tokens > 0) {
                    $user->tokens -= $skill->price_tokens;
                    $user->save();
                }

                CharacterSkill::create(['character_id' => $charId, 'skill_id' => $skillId]);

                return (object)[
                    'status' => 1,
                    'data' => (object)[
                        'character_gold' => $char->gold,
                        'account_tokens' => $user->tokens,
                        'character_element_1' => $char->element_1,
                        'character_element_2' => $char->element_2,
                        'character_element_3' => $char->element_3
                    ]
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function buyItem($charId, $sessionKey, $itemId, $quantity)
    {
        if ($quantity <= 0) {
            return (object)['status' => 0, 'error' => 'Invalid quantity'];
        }

        if (str_starts_with($itemId, 'skill_')) {
            return $this->buySkill($sessionKey, $charId, $itemId);
        }

        try {
            return DB::transaction(function () use ($charId, $itemId, $quantity, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

                $itemData = GameDataHelper::find_in_library($itemId);
                if (!$itemData) return (object)['status' => 0, 'error' => 'Item data not found!'];
                $item = (object)$itemData;

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($item->premium && $user->account_type == 0) {
                    return (object)['status' => 6, 'result' => 'Upgrade premium to buy!'];
                }

                if ($char->level < $item->level) return (object)['status' => 5, 'result' => 'Level too low!'];

                $totalGold = ($item->price_gold ?? 0) * $quantity;
                $totalTokens = ($item->price_tokens ?? 0) * $quantity;
                $totalPrestige = ($item->price_prestige ?? 0) * $quantity;

                if ($char->gold < $totalGold || $user->tokens < $totalTokens || ($char->prestige ?? 0) < $totalPrestige) return (object)['status' => 3, 'result' => 'Not enough resources!'];

                $char->gold -= $totalGold;
                $char->prestige = ($char->prestige ?? 0) - $totalPrestige;
                $char->save();

                if ($totalTokens > 0) {
                    $user->tokens -= $totalTokens;
                    $user->save();
                }

                $invItem = CharacterItem::where('character_id', $charId)->where('item_id', $itemId)->first();
                if ($invItem) {
                    $invItem->quantity += $quantity;
                    $invItem->save();
                } else {
                    $category = $item->category ?? 'item';
                    if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
                    elseif (str_starts_with($itemId, 'back_')) $category = 'back';
                    elseif (str_starts_with($itemId, 'set_')) $category = 'set';
                    elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
                    elseif (str_starts_with($itemId, 'material_')) $category = 'material';
                    elseif (str_starts_with($itemId, 'essential_')) $category = 'essential';
                    elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';

                    CharacterItem::create([
                        'character_id' => $charId, 'item_id' => $itemId, 'quantity' => $quantity, 'category' => $category
                    ]);
                }

                return (object)[
                    'status' => 1,
                    'error' => 0,
                    'data' => (object)[
                        'character_gold' => $char->gold,
                        'character_prestige' => $char->prestige,
                        'account_tokens' => $user->tokens
                    ]
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("buyItem error: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function sellItem($charId, $sessionKey, $itemId, $quantity)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
            
            if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

            $invItem = CharacterItem::where('character_id', $charId)->where('item_id', $itemId)->first();
            if (!$invItem || $invItem->quantity < $quantity) {
                return (object)['status' => 0, 'error' => 'Not enough items to sell!'];
            }

            $itemData = GameDataHelper::find_item($itemId);
            if (!$itemData) {
                return (object)['status' => 0, 'error' => 'Item data not found!'];
            }
            $itemConfig = (object)$itemData;

            $sellPriceOne = floor($itemConfig->price_gold / 2);
            $totalSellPrice = $sellPriceOne * $quantity;

            $char->gold += $totalSellPrice;
            $char->save();

            if ($invItem->quantity == $quantity) {
                $invItem->delete();
            } else {
                $invItem->quantity -= $quantity;
                $invItem->save();
            }

            return (object)[
                'status' => 1,
                'error' => 0,
                'data' => (object)[
                    'character_gold' => $char->gold
                ]
            ];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function getInventoryString($char, $category)
    {
        $items = \App\Models\CharacterItem::where('character_id', $char->id)
            ->where('category', $category)
            ->get();

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item->item_id . ':' . $item->quantity;
        }

        return implode(',', $parts);
    }

    private function getCodeString($char, $category)
    {
         $items = \App\Models\CharacterItem::where('character_id', $char->id)
            ->where('category', $category)
            ->get();

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item->item_id;
        }

        if ($category === 'animation') {
            $defaultAnimations = ['ani_1', 'ani_3', 'ani_5', 'ani_7', 'ani_9', 'ani_10', 'ani_11', 'ani_14'];
            $parts = array_unique(array_merge($parts, $defaultAnimations));
        }

        return implode(',', $parts);
    }

    private function getSkillsString($char)
    {
        $skills = \App\Models\CharacterSkill::where('character_id', $char->id)->get();
        
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id;
        }

        return implode(',', $parts);
    }

    private function getSenjutsuSkillsString($char)
    {
        $skills = \App\Models\CharacterSenjutsuSkill::where('character_id', $char->id)->get();
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id . ':' . $skill->level;
        }
        return implode(',', $parts);
    }

    public function buySenjutsuEssential($sessionKey, $charId, $quantity)
    {
        try {
            return DB::transaction(function () use ($sessionKey, $charId, $quantity) {
                 // Check user and char ownership
                $char = Character::lockForUpdate()->find($charId);
                // Assume session check is done or passed
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                $costPerItem = 10;
                $totalCost = $costPerItem * $quantity;
                
                if ($user->tokens < $totalCost) {
                    return (object)['status' => 2, 'error' => 'Not enough Tokens!'];
                }
                
                // Deduct tokens
                $user->tokens -= $totalCost;
                $user->save();
                
                // Add item: essential_11 (Ninja Seal Gan for Senjutsu?)
                // ResetSenjutsu.as uses 'essential_11'
                $itemId = 'essential_11';
                
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();
                    
                $newQuantity = 0;
                if ($invItem) {
                    $invItem->quantity += $quantity;
                    $invItem->save();
                    $newQuantity = $invItem->quantity;
                } else {
                    CharacterItem::create([
                        'character_id' => $charId,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'category' => 'essential'
                    ]);
                    $newQuantity = $quantity;
                }
                
                return (object)[
                    'status' => 1,
                    'result' => "Transaction Successful!",
                    'essential' => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function resetSenjutsu($charId, $sessionKey, $senjutsuSelected)
    {
        try {
            return DB::transaction(function () use ($charId, $senjutsuSelected) {
                // Cost: 5 Essentials (essential_11)
                $costItem = 'essential_11';
                // Cost calculation: Check logic in ResetSenjutsu.as?
                // Logic is: matCheck < 5 -> buyItem. Else confirmation.
                // So cost is 5.
                $costQty = 5;
                
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::find($char->user_id);
                if ($user && ($user->account_type == 1 || $user->emblem_duration > 0)) {
                    $costQty = 1;
                }
                
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $costItem)
                    ->first();
                    
                if (!$invItem || $invItem->quantity < $costQty) {
                    return (object)['status' => 2, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                if (empty($senjutsuSelected)) {
                    return (object)['status' => 0, 'error' => 'Please select senjutsu first!'];
                }
                
                // Deduct Cost
                $invItem->quantity -= $costQty;
                if ($invItem->quantity <= 0) {
                    $invItem->delete();
                } else {
                    $invItem->save();
                }
                
                // Convert senjutsuSelected names to actual stored values if necessary?
                // senjutsuSelected is passed as ["toad"] or similar from client?
                // ResetSenjutsu.as: this.senjutsuSelected.push(Character.character_senjutsu)
                // Character.character_senjutsu is "toad" or "snake".
                
                // Reset Logic:
                // 1. Clear character_senjutsu
                // 2. Clear character_equipped_senjutsu_skills
                // 3. Clear character_senjutsu_skills (table?)
                // ResetSenjutsu.as success handler:
                // Character.character_senjutsu = null;
                // Character.character_equipped_senjutsu_skills = "";
                // Character.character_senjutsu_skills = "";
                
                $char->senjutsu = null;
                $char->equipped_senjutsu_skills = null;
                $char->save();
                
                // Delete all senjutsu skills from table
                \App\Models\CharacterSenjutsuSkill::where('character_id', $charId)->delete();
                
                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function buyTalentEssential($sessionKey, $charId, $quantity)
    {
        try {
            return DB::transaction(function () use ($charId, $quantity) {
                // Fixed cost as per AS file
                $costPerItem = 200;
                $totalCost = $costPerItem * $quantity;

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $totalCost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }

                $user->tokens -= $totalCost;
                $user->save();

                $itemId = 'essential_02';
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();

                if ($invItem) {
                    $invItem->quantity += $quantity;
                    $invItem->save();
                    $newQuantity = $invItem->quantity;
                } else {
                    CharacterItem::create([
                        'character_id' => $charId,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'category' => 'essential'
                    ]);
                    $newQuantity = $quantity;
                }

                return (object)[
                    'status' => 1,
                    'essential' => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
        }
    }

    public function resetTalents($charId, $sessionKey, $talentsToReset)
    {
        try {
            return DB::transaction(function () use ($charId, $talentsToReset) {
                // Cost: 5 Essentials (essential_02)
                $costItem = 'essential_02';
                $costQty = 5;

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::find($char->user_id);
                if ($user && ($user->account_type == 1 || $user->emblem_duration > 0)) {
                    $costQty = 1;
                }

                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $costItem)
                    ->first();

                if (!$invItem || $invItem->quantity < $costQty) {
                    return (object)['status' => 2, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                if (empty($talentsToReset)) {
                    return (object)['status' => 0, 'error' => 'Please select talent first!'];
                }

                // Deduct items
                if ($invItem->quantity == $costQty) {
                    $invItem->delete();
                } else {
                    $invItem->quantity -= $costQty;
                    $invItem->save();
                }

                $totalTpRefund = 0;

                // talentsToReset is an array of IDs
                foreach ($talentsToReset as $talentId) {
                    if (!$talentId) continue;

                    // Clear from character slots
                    if ($char->talent_1 == $talentId) $char->talent_1 = null;
                    if ($char->talent_2 == $talentId) $char->talent_2 = null;
                    if ($char->talent_3 == $talentId) $char->talent_3 = null;

                    // Find skills to refund and delete
                    $skills = CharacterTalentSkill::where('character_id', $charId)
                        ->where('talent_id', $talentId)
                        ->get();

                    foreach ($skills as $skill) {
                        for ($l = 1; $l <= $skill->level; $l++) {
                            $totalTpRefund += $this->getTpCost($l);
                        }
                        $skill->delete();
                    }
                }

                $char->tp += $totalTpRefund;
                $char->save();

                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getGearPresets($charId, $sessionKey)
    {
        try {
            $presets = CharacterGearPreset::where('character_id', $charId)->get();
            \Illuminate\Support\Facades\Log::info("Found " . $presets->count() . " presets for char $charId");
            return (object)[
                'status' => 1,
                'presets' => array_map(function($p) { return (object)$p; }, $presets->toArray())
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in getGearPresets: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function updateGearPreset($charId, $sessionKey, $presetId, $weapon, $set, $hair, $back, $accessory, $hairColor)
    {
        try {
            $preset = CharacterGearPreset::where('id', $presetId)
                ->where('character_id', $charId)
                ->first();

            if (!$preset) return (object)['status' => 0, 'error' => 'Preset not found'];

            $preset->update([
                'weapon' => $weapon,
                'clothing' => $set,
                'hair' => $hair,
                'back_item' => $back,
                'accessory' => $accessory,
                'hair_color' => $hairColor
            ]);

            return (object)['status' => 1];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function renameGearPreset($charId, $sessionKey, $presetId, $name)
    {
        try {
            $preset = CharacterGearPreset::where('id', $presetId)
                ->where('character_id', $charId)
                ->first();

            if (!$preset) return (object)['status' => 0, 'error' => 'Preset not found'];

            $preset->name = $name;
            $preset->save();

            return (object)['status' => 1];
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function unlockGearPresetSlot($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $count = CharacterGearPreset::where('character_id', $charId)->count();
                $cost = ($count == 0) ? 0 : 500;

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                $user->tokens -= $cost;
                $user->save();

                CharacterGearPreset::create([
                    'character_id' => $charId,
                    'name' => 'Preset ' . ($count + 1),
                    'weapon' => $char->equipment_weapon ?: 'wpn_01',
                    'clothing' => $char->equipment_clothing ?: 'set_01',
                    'hair' => $char->hair_style ?: 'hair_01',
                    'back_item' => $char->equipment_back ?: 'back_01',
                    'accessory' => $char->equipment_accessory ?: 'accessory_01',
                    'hair_color' => $char->hair_color ?: '0|0',
                ]);

                return (object)[
                    'status' => 1,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function getTpCost($level)
    {
        return match ($level) {
            1 => 5,
            2 => 10,
            3 => 25,
            4 => 50,
            5 => 100,
            6 => 200,
            7 => 300,
            8 => 450,
            9 => 600,
            10 => 800,
            default => 0,
        };
    }

    public function getTalentSkillsString($char)
    {
        $skills = CharacterTalentSkill::where('character_id', $char->id)->get();
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id . ':' . $skill->level;
        }
        return implode(',', $parts);
    }

    public function getEquippedPetData($char)
    {
        if (!$char->equipped_pet_id) {
            return (object)[];
        }

        $pet = CharacterPet::where('character_id', $char->id)->find($char->equipped_pet_id);
        if (!$pet) {
            return (object)[];
        }

        return (object)[
            'pet_id' => $pet->id,
            'pet_name' => $pet->pet_name,
            'pet_level' => $pet->pet_level,
            'pet_swf' => $pet->pet_swf,
            'pet_skills' => $pet->pet_skills,
            'pet_mp' => $pet->pet_mp,
            'pet_xp' => $pet->pet_xp,
        ];
    }

    public function buyGanMaterial($sessionKey, $charId, $quantity)
    {
        try {
            return DB::transaction(function () use ($charId, $quantity) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                $costPerItem = 200;
                $totalCost = $costPerItem * $quantity;

                if ($user->tokens < $totalCost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }

                $user->tokens -= $totalCost;
                $user->save();

                $itemId = 'material_1001';
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();

                if ($invItem) {
                    $invItem->quantity += $quantity;
                    $invItem->save();
                    $newQuantity = $invItem->quantity;
                } else {
                    CharacterItem::create([
                        'character_id' => $charId,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'category' => 'material'
                    ]);
                    $newQuantity = $quantity;
                }

                return (object)[
                    'status' => 1,
                    'gan' => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function resetElements($charId, $sessionKey, $elementsToReset)
    {
        try {
            return DB::transaction(function () use ($charId, $elementsToReset) {
                \Illuminate\Support\Facades\Log::info("resetElements charId: $charId, elements: " . json_encode($elementsToReset));
                
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::find($char->user_id);
                $costQty = 5;
                if ($user && ($user->account_type == 1 || $user->emblem_duration > 0)) {
                    $costQty = 1;
                }

                $itemId = 'material_1001';
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();

                if (!$invItem || $invItem->quantity < $costQty) {
                    return (object)['status' => 3, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                // Ensure it is an array and filter invalid values
                $elements = [];
                if (is_array($elementsToReset)) {
                    $elements = $elementsToReset;
                } elseif (!empty($elementsToReset)) {
                    $elements = [$elementsToReset];
                }
                
                // Filter out empty values
                $elements = array_filter($elements, function($v) {
                    return !empty($v);
                });

                if (count($elements) === 0) {
                    return (object)['status' => 0, 'error' => 'Please select element first!'];
                }

                // Deduct materials
                $invItem->quantity -= $costQty;
                if ($invItem->quantity <= 0) {
                    $invItem->delete();
                } else {
                    $invItem->save();
                }

                foreach ($elements as $elemVal) {
                    $hasElement = false;
                    if ($char->element_1 == $elemVal) {
                        $char->element_1 = 0;
                        $hasElement = true;
                    } elseif ($char->element_2 == $elemVal) {
                        $char->element_2 = 0;
                        $hasElement = true;
                    } elseif ($char->element_3 == $elemVal) {
                        $char->element_3 = 0;
                        $hasElement = true;
                    }

                    if ($hasElement) {
                        $skillsToRemove = \App\Helpers\ElementSkillHelper::getSkillsForElement($elemVal);
                        if (!empty($skillsToRemove)) {
                            \App\Models\CharacterSkill::where('character_id', $charId)
                                ->whereIn('skill_id', $skillsToRemove)
                                ->delete();
                        }
                    }
                }
                $char->save();
                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("resetElements error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function useItem($charId, $sessionKey, $itemId)
    {
        try {
            return DB::transaction(function () use ($charId, $itemId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();

                if (!$invItem || $invItem->quantity <= 0) {
                    return (object)['status' => 2, 'result' => 'You do not have this item!'];
                }

                $resultMsg = "Item used successfully!";
                $rewards = "";

                // Logic based on itemId
                switch ($itemId) {
                    case 'essential_12':
                        $resultMsg = "Your Ninjutsu has been reset!";
                        $char->element_1 = null;
                        $char->element_2 = null;
                        $char->element_3 = null;
                        $char->save();
                        break;

                    case 'essential_03':
                    case 'essential_04':
                    case 'essential_05':
                    case 'essential_06':
                    case 'essential_07':
                    case 'essential_08':
                        if ($char->rank < 4) {
                            return (object)['status' => 2, 'result' => 'You must be a Jounin to use this item!'];
                        }

                        $tpAmount = 0;
                        if ($itemId == 'essential_03') $tpAmount = 5;
                        elseif ($itemId == 'essential_04') $tpAmount = 10;
                        elseif ($itemId == 'essential_05') $tpAmount = 20;
                        elseif ($itemId == 'essential_06') $tpAmount = 50;
                        elseif ($itemId == 'essential_07') $tpAmount = 100;
                        elseif ($itemId == 'essential_08') $tpAmount = 200;

                        $char->tp += $tpAmount;
                        $char->save();
                        $resultMsg = "You gained $tpAmount Talent Points!";
                        $rewards = "tp_~" . $tpAmount;
                        break;

                    case 'essential_02': // Full Talent Reset
                         if ($char->rank < 4) {
                            return (object)['status' => 2, 'result' => 'You must be a Jounin to use this item!'];
                        }

                        $totalTpRefund = 0;
                        $talents = [$char->talent_1, $char->talent_2, $char->talent_3];
                        
                        foreach ($talents as $talentId) {
                            if (!$talentId) continue;
                            
                            $skills = CharacterTalentSkill::where('character_id', $char->id)
                                ->where('talent_id', $talentId)
                                ->get();

                            foreach ($skills as $skill) {
                                for ($l = 1; $l <= $skill->level; $l++) {
                                    $totalTpRefund += $this->getTpCost($l);
                                }
                                $skill->delete();
                            }
                        }

                        $char->talent_1 = null;
                        $char->talent_2 = null;
                        $char->talent_3 = null;
                        $char->tp += $totalTpRefund;
                        $char->save();
                        $resultMsg = "Your Talents have been fully reset! Refunded $totalTpRefund TP.";
                        break;
                    
                    default:
                        return (object)['status' => 2, 'result' => 'This item cannot be used directly.'];
                }

                // Deduct item
                $invItem->quantity -= 1;
                if ($invItem->quantity <= 0) {
                    $invItem->delete();
                } else {
                    $invItem->save();
                }

                return (object)[
                    'status' => 1,
                    'result' => $resultMsg,
                    'rewards' => $rewards,
                    'data' => (object)[
                        'character_gold' => $char->gold,
                        'character_tokens' => User::find($char->user_id)->tokens ?? 0
                    ]
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    public function removeRecruitments($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if ($char) {
            // Remove this character from all recruited friends' recruiters lists
            $recruits = $char->recruits ?? [];
            foreach ($recruits as $recruitId) {
                $recruit = Character::find($recruitId);
                if ($recruit) {
                    $recruiters = $recruit->recruiters ?? [];
                    $recruiters = array_values(array_filter($recruiters, fn($id) => $id != $charId));
                    $recruit->recruiters = $recruiters;
                    $recruit->save();
                }
            }
            
            // Clear this character's recruits
            $char->recruits = [];
            $char->save();
        }
        
        return (object)[
            'status' => 1,
            'result' => 'Recruitments cleared.'
        ];
    }

    public function recruitTeammate($charId, $sessionKey, $recruitId)
    {
        try {
            return DB::transaction(function () use ($charId, $recruitId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return (object)['status' => 0, 'error' => 'Character not found'];
                }

                // Get current recruits
                $recruits = $char->recruits ?? [];

                // Limit check (max 2 recruits)
                if (count($recruits) >= 2) {
                     return (object)['status' => 2, 'result' => 'Team is full! Please dismiss a recruit first.'];
                }

                if (in_array($recruitId, $recruits)) {
                    return (object)['status' => 2, 'result' => 'Already recruited!'];
                }
                
                $recruits[] = $recruitId;
                $char->recruits = $recruits;
                $char->save();
                
                $friendIds = array_map(function($id) {
                    return str_starts_with($id, 'npc_') ? $id : 'char_' . $id;
                }, $recruits);
                
                $hash = !empty($friendIds) ? hash('sha256', (string)$friendIds[0]) : '';

                return (object)[
                    'status' => 1,
                    'recruiters' => [$friendIds, $hash]
                ];
            });
        } catch (\Exception $e) {
             \Illuminate\Support\Facades\Log::error("recruitTeammate error: " . $e->getMessage());
             return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
    public function changeSpecialClass($charId, $sessionKey, $newClassSkill)
    {
        try {
            return DB::transaction(function () use ($charId, $newClassSkill) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                // 1. Validate Level
                if ($char->level < 60) {
                     return (object)['status' => 2, 'result' => 'You must be Level 60 or higher!'];
                }

                // 2. Validate current class
                if ($char->class === $newClassSkill) {
                    return (object)['status' => 2, 'result' => 'You are already in this class!'];
                }

                // 3. Determine Cost
                // Emblem User (account_type 1) = 2000, others 3000
                $cost = ($user->account_type == 1) ? 2000 : 3000;

                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                // 4. Validate valid class skill
                $validClasses = ["skill_4002", "skill_4004", "skill_4001", "skill_4003", "skill_4000"];
                if (!in_array($newClassSkill, $validClasses)) {
                     return (object)['status' => 2, 'result' => 'Invalid Class Skill ID'];
                }

                // 5. Process Transaction
                $user->tokens -= $cost;
                $user->save();

                $char->class = $newClassSkill;
                $char->save();

                return (object)[
                    'status' => 1,
                    'result' => "Class changed successfully!"
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("CharacterService.changeSpecialClass error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function rename($sessionKey, $charId, $newName)
    {
        try {
            return DB::transaction(function () use ($charId, $newName) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return (object)['status' => 0, 'error' => 'Character not found'];
                }

                $user = User::find($char->user_id);

                // Validate name
                $newName = trim($newName);
                if (strlen($newName) < 3 || strlen($newName) > 16) {
                    return (object)['status' => 2, 'result' => 'Name must be between 3 and 16 characters'];
                }

                if (!preg_match('/^[a-zA-Z0-9_]+$/', $newName)) {
                    return (object)['status' => 2, 'result' => 'Name can only contain letters, numbers, and underscores'];
                }

                // Check uniqueness
                $exists = Character::where('name', $newName)
                    ->where('id', '!=', $charId)
                    ->exists();

                if ($exists) {
                    return (object)['status' => 2, 'result' => 'This name is already taken!'];
                }

                // Determine cost (premium = 1, free = 3)
                $badgeCost = ($user && $user->account_type == 1) ? 1 : 3;
                $badgeId   = 'essential_01';

                $badgeItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $badgeId)
                    ->first();

                if (!$badgeItem || $badgeItem->quantity < $badgeCost) {
                    return (object)['status' => 2, 'result' => 'Not enough Rename Badges!'];
                }

                // Deduct badges
                $badgeItem->quantity -= $badgeCost;
                if ($badgeItem->quantity <= 0) {
                    $badgeItem->delete();
                } else {
                    $badgeItem->save();
                }

                // Update name
                $char->name = $newName;
                $char->save();

                return (object)[
                    'status' => 1,
                    'result' => 'Name changed successfully!'
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("CharacterService.rename error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function buyRenameBadge($sessionKey, $charId, $amount)
    {
        try {
            return DB::transaction(function () use ($charId, $amount) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return (object)['status' => 0, 'error' => 'Character not found'];
                }

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) {
                    return (object)['status' => 0, 'error' => 'User not found'];
                }

                $pricePerBadge = 200;
                $totalCost     = $pricePerBadge * $amount;

                if ($user->tokens < $totalCost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }

                $user->tokens -= $totalCost;
                $user->save();

                $badgeId  = 'essential_01';
                $invItem  = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $badgeId)
                    ->first();

                if ($invItem) {
                    $invItem->quantity += $amount;
                    $invItem->save();
                    $newQuantity = $invItem->quantity;
                } else {
                    CharacterItem::create([
                        'character_id' => $charId,
                        'item_id'      => $badgeId,
                        'quantity'     => $amount,
                        'category'     => 'essential'
                    ]);
                    $newQuantity = $amount;
                }

                return (object)[
                    'status' => 1,
                    'badge'  => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("CharacterService.buyRenameBadge error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function validateSession($userId, $sessionKey)
    {
        $user = User::find($userId);

        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }
    private function getAnimationList()
    {
        return [
          [
            "id" => "ani_1",
            "name" => "Dodge",
            "category" => "dodge",
            "label" => "dodge",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_2",
            "name" => "Dodge Snowman",
            "category" => "dodge",
            "label" => "dodge_snowman",
            "price" => 1000,
            "buyable" => true,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_3",
            "name" => "Dead",
            "category" => "dead",
            "label" => "dead",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_4",
            "name" => "Halloween Dead",
            "category" => "dead",
            "label" => "halloween_dead_standby",
            "price" => 1000,
            "buyable" => true,
            "premium" => false,
            "loop" => true
          ],
          [
            "id" => "ani_5",
            "name" => "Standby",
            "category" => "standby",
            "label" => "standby",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => true
          ],
          [
            "id" => "ani_6",
            "name" => "Halloween Standby",
            "category" => "standby",
            "label" => "halloween_self_standby",
            "price" => 1000,
            "buyable" => true,
            "premium" => false,
            "loop" => true
          ],
          [
            "id" => "ani_7",
            "name" => "Win",
            "category" => "win",
            "label" => "win",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_8",
            "name" => "Firework",
            "category" => "win",
            "label" => "firework",
            "price" => 2000,
            "buyable" => true,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_9",
            "name" => "Charge",
            "category" => "charge",
            "label" => "charge",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_10",
            "name" => "Hit",
            "category" => "hit",
            "label" => "hit",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_11",
            "name" => "Run",
            "category" => "smoke",
            "label" => "smoke",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_12",
            "name" => "Standby Rebel",
            "category" => "standby",
            "label" => "standby_rebel",
            "price" => 1000,
            "buyable" => true,
            "premium" => false,
            "loop" => true
          ],
          [
            "id" => "ani_13",
            "name" => "Dodge Bat",
            "category" => "dodge",
            "label" => "dodge_bat",
            "price" => 1000,
            "buyable" => true,
            "premium" => false,
            "loop" => false
          ],
          [
            "id" => "ani_14",
            "name" => "Standby Mekkorvath",
            "category" => "standby",
            "label" => "standby_mechfuku",
            "price" => 0,
            "buyable" => false,
            "premium" => false,
            "loop" => true
          ]
        ];
    }

    public function buyAnimation($charId, $sessionKey, $animId)
    {
        try {
            return DB::transaction(function () use ($charId, $sessionKey, $animId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                // Check if already owned
                $exists = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $animId)
                    ->exists();

                if ($exists) {
                    return (object)['status' => 2, 'result' => 'You already own this animation!'];
                }

                $animList = $this->getAnimationList();
                $targetAnim = null;
                foreach ($animList as $anim) {
                    if ($anim['id'] === $animId) {
                        $targetAnim = $anim;
                        break;
                    }
                }

                if (!$targetAnim) {
                    return (object)['status' => 0, 'result' => 'Animation not found!'];
                }

                if (!$targetAnim['buyable']) {
                    return (object)['status' => 0, 'result' => 'This animation is not for sale!'];
                }

                if ($targetAnim['premium'] && $user->account_type == 0) {
                     return (object)['status' => 6, 'result' => 'Premium users only!'];
                }

                $price = $targetAnim['price'];

                if ($user->tokens < $price) {
                     return (object)['status' => 0, 'result' => 'Not enough tokens!'];
                }

                if ($price > 0) {
                    $user->tokens -= $price;
                    $user->save();
                }

                CharacterItem::create([
                    'character_id' => $charId,
                    'item_id' => $animId,
                    'quantity' => 1,
                    'category' => 'animation'
                ]);

                return (object)[
                    'status' => 1,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("buyAnimation error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function useAnimation($charId, $sessionKey, $animId)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
            
            if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

            $owns = CharacterItem::where('character_id', $charId)
                ->where('item_id', $animId)
                ->exists();
            
            if (!$owns) {
                 return (object)['status' => 0, 'result' => 'You do not own this animation!'];
            }

            $animList = $this->getAnimationList();
            $targetAnim = null;
            foreach ($animList as $anim) {
                if ($anim['id'] === $animId) {
                    $targetAnim = $anim;
                    break;
                }
            }

            if (!$targetAnim) {
                return (object)['status' => 0, 'result' => 'Animation data not found!'];
            }

            $equipped = $char->equipped_animations ? json_decode($char->equipped_animations, true) : [];
            if (!is_array($equipped)) $equipped = [];
            
            $equipped[$targetAnim['category']] = $animId;
            
            $char->equipped_animations = json_encode($equipped);
            $char->save();
            
            return (object)['status' => 1];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("useAnimation error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
    public function getAnimations($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
            
            if (!$this->validateSession($char->user_id, $sessionKey)) return (object)['status' => 0, 'error' => 'Session expired!'];

            $items = CharacterItem::where('character_id', $charId)
                ->where('category', 'animation')
                ->get()
                ->pluck('item_id')
                ->toArray();

            $equipped = $char->equipped_animations ? json_decode($char->equipped_animations, true) : [];
            if (!is_array($equipped)) $equipped = [];

            $animList = $this->getAnimationList();
            $result = [];

            foreach ($animList as $anim) {
                // Determine ownership
                $isOwned = false;
                if (!$anim['buyable'] && $anim['price'] == 0) {
                    $isOwned = true; // Default/Free animations are owned
                } elseif (in_array($anim['id'], $items)) {
                    $isOwned = true;
                }

                // Determine equipped
                $isEquipped = false;
                if (isset($equipped[$anim['category']]) && $equipped[$anim['category']] === $anim['id']) {
                    $isEquipped = true;
                }

                $resObj = (object)$anim;
                $resObj->owned = $isOwned;
                $resObj->equipped = $isEquipped;
                
                $result[] = $resObj;
            }

            return (object)[
                'status' => 1,
                'animations' => $result
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("getAnimations error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}



