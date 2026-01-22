<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterPet;
use App\Models\CharacterSkill;
use App\Models\CharacterTalentSkill;
use App\Models\CharacterGearPreset;
use App\Models\User;
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
            return ['status' => 2, 'error' => 'Character name too short.'];
        }

        $check = Character::where('name', $charName)->first();
        if ($check != null) {
            return ['status' => 2, 'error' => 'Character name already taken.'];
        }

        try {
            $char = new Character();
            $char->user_id = $accountId;
            $char->name = $charName;
            $char->gender = $gender;
            $char->element_1 = $element;
            $char->hair_style = $hairNum;
            $char->hair_color = $hairColor;
            $char->point_free = 1;
            $char->save();

            return ['status' => 1];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function equipSet($charId, $sessionKey, $weapon, $backItem, $clothing, $accessory, $hair, $hairColor, $skinColor)
    {
        try {
            $char = Character::find($charId);
            if ($char == null) {
                return ['status' => 0, 'error' => 'Character not found'];
            }

            $char->equipment_weapon = $weapon;
            $char->equipment_back = $backItem;
            $char->equipment_clothing = $clothing;
            $char->equipment_accessory = $accessory;
            $char->hair_style = $hair;
            $char->hair_color = $hairColor;
            $char->skin_color = $skinColor;
            $char->save();

            return ['status' => 1];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function setPoints($charId, $sessionKey, $wind, $fire, $lightning, $water, $earth, $free)
    {
        try {
            $char = Character::find($charId);
            if ($char == null) {
                return ['status' => 0, 'error' => 'Character not found'];
            }

            $char->point_wind = $wind;
            $char->point_fire = $fire;
            $char->point_lightning = $lightning;
            $char->point_water = $water;
            $char->point_earth = $earth;
            $char->point_free = $free;
            $char->save();

            return ['status' => 1, 'error' => 0];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function equipSkillSet($charId, $sessionKey, $skillString)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return ['status' => 0, 'error' => 'Character not found'];

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

            return ['status' => 1, 'error' => 0];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getSkillSets($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $skillsets = \App\Models\CharacterSkillSet::where('character_id', $charId)
            ->orderBy('preset_index')
            ->get();

        $data = [];
        foreach ($skillsets as $s) {
            $data[] = [
                'id' => $s->id,
                'skills' => $s->skills ?: ""
            ];
        }

        return [
            'status' => 1,
            'skillsets' => $data
        ];
    }

    public function createSkillSet($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                $count = \App\Models\CharacterSkillSet::where('character_id', $charId)->count();
                
                $cost = ($user->account_type == 1) ? 200 : 600;
                if ($count == 0) {
                    $cost = 0;
                }

                if ($user->tokens < $cost) {
                    return ['status' => 2, 'result' => 'Not enough tokens!'];
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
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function saveSkillSet($charId, $sessionKey, $presetId, $skills)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $preset = \App\Models\CharacterSkillSet::where('id', $presetId)
            ->where('character_id', $charId)
            ->first();

        if (!$preset) return ['status' => 2, 'result' => 'Preset not found!'];

        $preset->skills = $skills;
        $preset->save();

        $char->equipment_skills = $skills;
        $char->save();

        return $this->getSkillSets($charId, $sessionKey);
    }

    public function getInfo($charId, $sessionKey, $targetId, $type = null)
    {
        // Handle 'char_' prefix often sent by the client
        if (str_starts_with($targetId, 'char_')) {
            $targetId = str_replace('char_', '', $targetId);
        }

        $char = Character::with('user')->find($targetId);

        if ($char == null) {
            return ['status' => 0, 'error' => 'Character not found'];
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

        return [
            'status' => 1,
            'error' => 0,
            'friend' => $isFriend,
            'account_type' => $char->user ? $char->user->account_type : 0,
            'emblem_duration' => $char->user ? $char->user->emblem_duration : -1,
            'events' => [],
            'character_data' => [
                'character_id' => $char->id,
                'character_name' => $char->name,
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
                'character_class' => null,
                'character_senjutsu' => $char->senjutsu ? strtolower($char->senjutsu) : null,
                'character_pvp_points' => 0,
                'welcome_status' => (function() use ($char) {
                    $welcome = \App\Models\CharacterWelcomeLogin::where('character_id', $char->id)->first();
                    if (!$welcome) return 0;
                    $claimed = $welcome->claimed_days ?? [];
                    return count($claimed) >= 7 ? 1 : 0;
                })()
            ],
            'character_points' => [
                'atrrib_wind' => $char->point_wind,
                'atrrib_fire' => $char->point_fire,
                'atrrib_lightning' => $char->point_lightning,
                'atrrib_water' => $char->point_water,
                'atrrib_earth' => $char->point_earth,
                'atrrib_free' => $char->point_free
            ],
            'character_slots' => [
                'weapons' => 100,
                'back_items' => 100,
                'accessories' => 100,
                'hairstyles' => 100,
                'clothing' => 100
            ],
            'character_sets' => [
                'weapon' => $weapon,
                'back_item' => $backItem,
                'accessory' => $accessory,
                'hairstyle' => $hairstyle,
                'clothing' => $clothing,
                'skills' => $skills,
                'senjutsu_skills' => $char->equipped_senjutsu_skills,
                'hair_color' => $hairColor,
                'skin_color' => $skinColor,
                'face' => 'face_01' . $genderSuffix,
                'pet' => $char->equipped_pet_id ? (\App\Models\CharacterPet::find($char->equipped_pet_id)->pet_swf ?? null) : null,
                'anims' => []
            ],
            'character_inventory' => [
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
                'char_animations' => ''
            ],
            'features' => [],
            'recruiters' => array_map(function($id) { return 'char_' . $id; }, Cache::get('character_recruits_' . $charId, [])),
            'recruit_data' => array_map(function($id) {
                $c = Character::with('user')->find($id);
                return $c ? app(FriendService::class)->formatFriendData($c) : null;
            }, array_filter(Cache::get('character_recruits_' . $charId, []))),
            'pet_data' => $this->getEquippedPetData($char),
            'clan' => null
        ];
    }

    public function getMissionRoomData($charId, $sessionKey)
    {
        $recruitIds = Cache::get('character_recruits_' . $charId, []);
        $recruitData = [];
        
        foreach ($recruitIds as $id) {
            $friendChar = Character::with('user')->find($id);
            if ($friendChar) {
                $formatted = app(FriendService::class)->formatFriendData($friendChar);
                $recruitData[] = [
                    'id' => $id,
                    'type' => 'char',
                    'info' => $formatted
                ];
            }
        }

        return [
            'status' => 1,
            'error' => 0,
            'recruit' => $recruitData,
            'daily' => []
        ];
    }

    public function buySkill($sessionKey, $charId, $skillId)
    {
        try {
            return DB::transaction(function () use ($charId, $skillId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                if (CharacterSkill::where('character_id', $charId)->where('skill_id', $skillId)->exists()) {
                    return ['status' => 2];
                }

                $json = file_get_contents(storage_path('app/skills.json'));
                $skills = json_decode($json, true);
                $skill = null;
                foreach ($skills as $s) {
                    if ($s['skill_id'] == $skillId) {
                        $skill = (object)$s;
                        break;
                    }
                }

                if (!$skill) return ['status' => 0, 'error' => 'Skill data not found!'];

                if ($skill->premium && $user->account_type == 0) {
                    return ['status' => 6];
                }

                if ($char->level < $skill->level) return ['status' => 5];

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
                            return ['status' => 4];
                        }
                    }
                }

                if ($char->gold < $skill->price_gold || $user->tokens < $skill->price_tokens) return ['status' => 3];

                $char->gold -= $skill->price_gold;
                $char->save();

                if ($skill->price_tokens > 0) {
                    $user->tokens -= $skill->price_tokens;
                    $user->save();
                }

                CharacterSkill::create(['character_id' => $charId, 'skill_id' => $skillId]);

                return [
                    'status' => 1,
                    'data' => [
                        'character_gold' => $char->gold,
                        'account_tokens' => $user->tokens,
                        'character_element_1' => $char->element_1,
                        'character_element_2' => $char->element_2,
                        'character_element_3' => $char->element_3
                    ]
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function buyItem($charId, $sessionKey, $itemId, $quantity)
    {
        if ($quantity <= 0) {
            return ['status' => 0, 'error' => 'Invalid quantity'];
        }

        if (str_starts_with($itemId, 'skill_')) {
            return $this->buySkill($sessionKey, $charId, $itemId);
        }

        try {
            return DB::transaction(function () use ($charId, $itemId, $quantity) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $json = file_get_contents(storage_path('app/library.json'));
                $items = json_decode($json, true);
                $item = null;
                foreach ($items as $i) {
                    if ($i['id'] == $itemId) {
                        $item = (object)$i;
                        break;
                    }
                }

                if (!$item) return ['status' => 0, 'error' => 'Item data not found!'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                if ($item->premium && $user->account_type == 0) {
                    return ['status' => 6, 'result' => 'Upgrade premium to buy!'];
                }

                if ($char->level < $item->level) return ['status' => 5, 'result' => 'Level too low!'];

                $totalGold = $item->price_gold * $quantity;
                $totalTokens = $item->price_tokens * $quantity;

                if ($char->gold < $totalGold || $user->tokens < $totalTokens) return ['status' => 3, 'result' => 'Not enough resources!'];

                $char->gold -= $totalGold;
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
                    CharacterItem::create([
                        'character_id' => $charId, 'item_id' => $itemId, 'quantity' => $quantity, 'category' => $item->category
                    ]);
                }

                return [
                    'status' => 1,
                    'error' => 0,
                    'data' => [
                        'character_gold' => $char->gold,
                        'character_prestige' => $char->prestige,
                        'account_tokens' => $user->tokens
                    ]
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function sellItem($charId, $sessionKey, $itemId, $quantity)
    {
        try {
            $char = Character::find($charId);
            if (!$char) return ['status' => 0, 'error' => 'Character not found'];

            $invItem = CharacterItem::where('character_id', $charId)->where('item_id', $itemId)->first();
            if (!$invItem || $invItem->quantity < $quantity) {
                return ['status' => 0, 'error' => 'Not enough items to sell!'];
            }

            $json = file_get_contents(storage_path('app/items.json'));
            $items = json_decode($json, true);
            $itemConfig = null;
            foreach ($items as $i) {
                if ($i['item_id'] == $itemId) {
                    $itemConfig = (object)$i;
                    break;
                }
            }

            if (!$itemConfig) {
                return ['status' => 0, 'error' => 'Item data not found!'];
            }

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

            return [
                'status' => 1,
                'error' => 0,
                'data' => [
                    'character_gold' => $char->gold
                ]
            ];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
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
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                $costPerItem = 10;
                $totalCost = $costPerItem * $quantity;
                
                if ($user->tokens < $totalCost) {
                    return ['status' => 2, 'error' => 'Not enough Tokens!'];
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
                
                return [
                    'status' => 1,
                    'result' => "Transaction Successful!",
                    'essential' => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
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
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::find($char->user_id);
                if ($user && ($user->account_type == 1 || $user->emblem_duration > 0)) {
                    $costQty = 1;
                }
                
                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $costItem)
                    ->first();
                    
                if (!$invItem || $invItem->quantity < $costQty) {
                    return ['status' => 2, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                if (empty($senjutsuSelected)) {
                    return ['status' => 0, 'error' => 'Please select senjutsu first!'];
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
                
                return ['status' => 1];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
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
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $totalCost) {
                    return ['status' => 2, 'result' => 'Not enough tokens'];
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

                return [
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
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::find($char->user_id);
                if ($user && ($user->account_type == 1 || $user->emblem_duration > 0)) {
                    $costQty = 1;
                }

                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $costItem)
                    ->first();

                if (!$invItem || $invItem->quantity < $costQty) {
                    return ['status' => 2, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                if (empty($talentsToReset)) {
                    return ['status' => 0, 'error' => 'Please select talent first!'];
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

                return ['status' => 1];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getGearPresets($charId, $sessionKey)
    {
        try {
            $presets = CharacterGearPreset::where('character_id', $charId)->get();
            \Illuminate\Support\Facades\Log::info("Found " . $presets->count() . " presets for char $charId");
            return [
                'status' => 1,
                'presets' => $presets->toArray()
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in getGearPresets: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function updateGearPreset($charId, $sessionKey, $presetId, $weapon, $set, $hair, $back, $accessory, $hairColor)
    {
        try {
            $preset = CharacterGearPreset::where('id', $presetId)
                ->where('character_id', $charId)
                ->first();

            if (!$preset) return ['status' => 0, 'error' => 'Preset not found'];

            $preset->update([
                'weapon' => $weapon,
                'clothing' => $set,
                'hair' => $hair,
                'back_item' => $back,
                'accessory' => $accessory,
                'hair_color' => $hairColor
            ]);

            return ['status' => 1];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function renameGearPreset($charId, $sessionKey, $presetId, $name)
    {
        try {
            $preset = CharacterGearPreset::where('id', $presetId)
                ->where('character_id', $charId)
                ->first();

            if (!$preset) return ['status' => 0, 'error' => 'Preset not found'];

            $preset->name = $name;
            $preset->save();

            return ['status' => 1];
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function unlockGearPresetSlot($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $count = CharacterGearPreset::where('character_id', $charId)->count();
                $cost = ($count == 0) ? 0 : 500;

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $cost) {
                    return ['status' => 2, 'result' => 'Not enough tokens!'];
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

                return [
                    'status' => 1,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
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
            return [];
        }

        $pet = CharacterPet::where('character_id', $char->id)->find($char->equipped_pet_id);
        if (!$pet) {
            return [];
        }

        return [
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
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                $costPerItem = 200;
                $totalCost = $costPerItem * $quantity;

                if ($user->tokens < $totalCost) {
                    return ['status' => 2, 'result' => 'Not enough tokens'];
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

                return [
                    'status' => 1,
                    'gan' => $newQuantity,
                    'tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function resetElements($charId, $sessionKey, $elementsToReset)
    {
        try {
            return DB::transaction(function () use ($charId, $elementsToReset) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

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
                    return ['status' => 3, 'error' => 'Not enough Ninja Seal Gan!'];
                }

                if (empty($elementsToReset)) {
                    return ['status' => 0, 'error' => 'Please select element first!'];
                }

                // Deduct materials
                $invItem->quantity -= $costQty;
                if ($invItem->quantity <= 0) {
                    $invItem->delete();
                } else {
                    $invItem->save();
                }

                foreach ($elementsToReset as $elemVal) {
                    if ($char->element_1 == $elemVal) $char->element_1 = null;
                    elseif ($char->element_2 == $elemVal) $char->element_2 = null;
                    elseif ($char->element_3 == $elemVal) $char->element_3 = null;
                }
                $char->save();
                return ['status' => 1];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function useItem($charId, $sessionKey, $itemId)
    {
        try {
            return DB::transaction(function () use ($charId, $itemId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $invItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $itemId)
                    ->first();

                if (!$invItem || $invItem->quantity <= 0) {
                    return ['status' => 2, 'result' => 'You do not have this item!'];
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
                            return ['status' => 2, 'result' => 'You must be a Jounin to use this item!'];
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
                            return ['status' => 2, 'result' => 'You must be a Jounin to use this item!'];
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
                        return ['status' => 2, 'result' => 'This item cannot be used directly.'];
                }

                // Deduct item
                $invItem->quantity -= 1;
                if ($invItem->quantity <= 0) {
                    $invItem->delete();
                } else {
                    $invItem->save();
                }

                return [
                    'status' => 1,
                    'result' => $resultMsg,
                    'rewards' => $rewards,
                    'data' => [
                        'character_gold' => $char->gold,
                        'character_tokens' => User::find($char->user_id)->tokens ?? 0
                    ]
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    public function removeRecruitments($charId, $sessionKey)
    {
        Cache::forget('character_recruits_' . $charId);
        return [
            'status' => 1,
            'result' => 'Recruitments cleared.'
        ];
    }
}

