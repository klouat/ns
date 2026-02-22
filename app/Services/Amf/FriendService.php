<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Friend;
use App\Models\User;
use App\Models\FriendshipShopItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FriendService
{
    private function shopItems()
    {
        // Return as raw array to match original structure exactly
        return FriendshipShopItem::select('id', 'price', 'item')
            ->get()
            ->map(function ($item) {
                // Fix for client rendering: client expects 'tokens' plural
                $itemStr = (string)$item->item;
                if (str_starts_with($itemStr, 'token_')) {
                    $itemStr = str_replace('token_', 'tokens_', $itemStr);
                } elseif (str_starts_with($itemStr, 'skills_')) {
                    // Fix for client rendering: client expects 'skill' singular
                    $itemStr = str_replace('skills_', 'skill_', $itemStr);
                }
                
                return (object)[
                    'id' => (int)$item->id,
                    'price' => (int)$item->price,
                    'item' => $itemStr
                ];
            })
            ->toArray();
    }
    public function friends($charId, $sessionKey, $page = 1)
    {
        $limit = 8;
        $offset = ($page - 1) * $limit;

        $friendsQuery = Friend::where('character_id', $charId)
        ->where('status', 1);

        $total = $friendsQuery->count();
        $friends = $friendsQuery->offset($offset)->limit($limit)->get();

        $friendList = [];
        foreach ($friends as $f) {
            $friendChar = Character::with('user')->find($f->friend_id);
            if ($friendChar) {
                $friendList[] = $this->formatFriendData($friendChar, $f->is_favorite);
            }
        }

        $currentChar = Character::find($charId);

        return (object)[
            'status' => 1,
            'friends' => $friendList,
            'page' => (object)[
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'limit' => 10000, 
            'total' => $total,
            'recruitable' => $currentChar ? (bool)$currentChar->is_recruitable : true
        ];
    }


    public function getFavorite($charId, $sessionKey, $page = 1)
    {
        $limit = 8;
        $offset = ($page - 1) * $limit;

        $friendsQuery = Friend::where('character_id', $charId)
            ->where('status', 1)
            ->where('is_favorite', true);

        $total = $friendsQuery->count();
        $friends = $friendsQuery->offset($offset)->limit($limit)->get();

        $friendList = [];
        foreach ($friends as $f) {
            $friendChar = Character::with('user')->find($f->friend_id);
            if ($friendChar) {
                $friendList[] = $this->formatFriendData($friendChar, true);
            }
        }

        return (object)[
            'status' => 1,
            'friends' => $friendList,
            'page' => (object)[
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'limit' => 100,
            'total' => $total
        ];
    }

    public function friendRequests($charId, $sessionKey, $page = 1)
    {
        $limit = 4; // Social.as uses 4 for requests
        $offset = ($page - 1) * $limit;

        $requestsQuery = Friend::where('friend_id', $charId)
            ->where('status', 0);

        $total = $requestsQuery->count();
        $requests = $requestsQuery->offset($offset)->limit($limit)->get();

        $invitations = [];
        foreach ($requests as $r) {
            $requesterChar = Character::with('user')->find($r->character_id);
            if ($requesterChar) {
                $invitations[] = $this->formatFriendData($requesterChar);
            }
        }

        return (object)[
            'status' => 1,
            'invitations' => $invitations,
            'page' => (object)[
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'total' => $total
        ];
    }

    public function addFriend($charId, $sessionKey, $targetFriendId)
    {
        if ($charId == $targetFriendId) {
            return (object)['status' => 0, 'result' => 'You cannot add yourself as a friend.'];
        }

        $existing = Friend::where('character_id', $charId)
            ->where('friend_id', $targetFriendId)
            ->first();

        if ($existing) {
            if ($existing->status == 1) {
                return (object)['status' => 0, 'result' => 'Already friends.'];
            } else {
                return (object)['status' => 0, 'result' => 'Friend request already sent.'];
            }
        }

        Friend::create([
            'character_id' => $charId,
            'friend_id' => $targetFriendId,
            'status' => 0
        ]);

        return (object)[
            'status' => 1,
            'result' => 'Friend request sent!'
        ];
    }

    public function acceptFriend($charId, $sessionKey, $requesterId)
    {
        $request = Friend::where('character_id', $requesterId)
            ->where('friend_id', $charId)
            ->where('status', 0)
            ->first();

        if (!$request) {
            return (object)['status' => 0, 'result' => 'Friend request not found.'];
        }

        DB::transaction(function () use ($request, $charId, $requesterId) {
            $request->status = 1;
            $request->save();

            // Create mutual friendship
            Friend::updateOrCreate(
                ['character_id' => $charId, 'friend_id' => $requesterId],
                ['status' => 1]
            );
        });

        return (object)[
            'status' => 1,
            'result' => 'Friend request accepted!'
        ];
    }

    public function removeFriend($charId, $sessionKey, $friendId)
    {
        if (is_array($friendId)) {
            Friend::where('character_id', $charId)->whereIn('friend_id', $friendId)->delete();
            Friend::where('friend_id', $charId)->whereIn('character_id', $friendId)->delete();
        } else {
            Friend::where('character_id', $charId)->where('friend_id', $friendId)->delete();
            Friend::where('friend_id', $charId)->where('character_id', $friendId)->delete();
        }

        return (object)[
            'status' => 1,
            'result' => 'Friend removed.'
        ];
    }

    public function recruitable($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'result' => 'Character not found'];

        $char->is_recruitable = !$char->is_recruitable;
        $char->save();

        return (object)[
            'status' => 1,
            'recruitable' => (bool)$char->is_recruitable
        ];
    }

    public function setFavorite($charId, $sessionKey, $friendId)
    {
        $friend = Friend::where('character_id', $charId)
            ->where('friend_id', $friendId)
            ->where('status', 1)
            ->first();

        if (!$friend) return (object)['status' => 0, 'result' => 'Friend not found.'];

        $friend->is_favorite = true;
        $friend->save();

        return (object)[
            'status' => 1,
            'result' => 'Added to favorites!'
        ];
    }

    public function removeFavorite($charId, $sessionKey, $friendId)
    {
        $friend = Friend::where('character_id', $charId)
            ->where('friend_id', $friendId)
            ->where('status', 1)
            ->first();

        if (!$friend) return (object)['status' => 0, 'result' => 'Friend not found.'];

        $friend->is_favorite = false;
        $friend->save();

        return (object)[
            'status' => 1,
            'result' => 'Removed from favorites!'
        ];
    }

    public function getRecommendations($charId, $sessionKey, $page = 1)
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;

        // Simple recommendation: just some characters that are not already friends
        $alreadyFriends = Friend::where('character_id', $charId)->pluck('friend_id')->toArray();
        array_push($alreadyFriends, $charId);

        $recommendQuery = Character::whereNotIn('id', $alreadyFriends);
        
        $total = $recommendQuery->count();
        $recommendChars = $recommendQuery->offset($offset)->limit($limit)->get();

        $recommendations = [];
        foreach ($recommendChars as $c) {
            $recommendations[] = $this->formatFriendData($c);
        }

        return (object)[
            'status' => 1,
            'recommendations' => $recommendations,
            'page' => (object)[
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ]
        ];
    }

    public function search($charId, $sessionKey, $query, $page = 1)
    {
        $limit = 8;
        $offset = ($page - 1) * $limit;

        // Search by character ID or name
        $characters = Character::where('id', '!=', $charId)
            ->where(function ($q) use ($query) {
                $q->where('id', $query)
                  ->orWhere('name', 'like', '%' . $query . '%');
            })
            ->get();

        $friendList = [];
        foreach ($characters as $c) {
            $isFriend = Friend::where('character_id', $charId)
                ->where('friend_id', $c->id)
                ->where('status', 1)
                ->exists();
            
            $isFavorite = Friend::where('character_id', $charId)
                ->where('friend_id', $c->id)
                ->where('is_favorite', true)
                ->exists();

            $friendList[] = $this->formatFriendData($c, $isFavorite);
        }

        $total = count($friendList);
        $friendList = array_slice($friendList, $offset, $limit);

        return (object)[
            'status' => 1,
            'friends' => $friendList,
            'page' => (object)[
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'limit' => 100,
            'total' => $total
        ];
    }

    public function unfriendAll($charId, $sessionKey)
    {
        Friend::where('character_id', $charId)->delete();
        Friend::where('friend_id', $charId)->delete();

        return (object)[
            'status' => 1,
            'result' => 'All friends removed.'
        ];
    }

    public function acceptAll($charId, $sessionKey)
    {
        $requests = Friend::where('friend_id', $charId)
            ->where('status', 0)
            ->get();

        foreach ($requests as $r) {
            DB::transaction(function () use ($r, $charId) {
                $r->status = 1;
                $r->save();

                Friend::updateOrCreate(
                    ['character_id' => $charId, 'friend_id' => $r->character_id],
                    ['status' => 1]
                );
            });
        }

        return (object)[
            'status' => 1,
            'result' => 'All friend requests accepted!'
        ];
    }

    public function removeAll($charId, $sessionKey)
    {
        Friend::where('friend_id', $charId)
            ->where('status', 0)
            ->delete();

        return (object)[
            'status' => 1,
            'result' => 'All friend requests rejected.'
        ];
    }

    public function recruitFriend($charId, $sessionKey, $friendId)
    {
        // 1. Check if they are friends
        $isFriend = \App\Models\Friend::where('character_id', $charId)
            ->where('friend_id', $friendId)
            ->where('status', 1)
            ->exists();

        if (!$isFriend) {
            return (object)['status' => 2, 'result' => 'You can only recruit friends!'];
        }

        // 2. Check if recruitable
        $friend = Character::find($friendId);
        if (!$friend || !$friend->is_recruitable) {
             return (object)['status' => 2, 'result' => 'This friend is not recruitable right now.'];
        }

        // 3. Store in database for persistence
        $char = Character::find($charId);
        $recruits = $char->recruits ?? [];
        
        if (!in_array($friendId, $recruits)) {
            if (count($recruits) >= 2) {
                // Max 2 friends can be recruited in Ninja Saga
                // Remove the oldest recruit from both sides
                $oldestRecruitId = array_shift($recruits);
                
                // Remove this character from the old recruit's recruiters list
                $oldRecruit = Character::find($oldestRecruitId);
                if ($oldRecruit) {
                    $oldRecruiters = $oldRecruit->recruiters ?? [];
                    $oldRecruiters = array_values(array_filter($oldRecruiters, fn($id) => $id != $charId));
                    $oldRecruit->recruiters = $oldRecruiters;
                    $oldRecruit->save();
                }
            }
            
            // Add new recruit
            $recruits[] = $friendId;
            $char->recruits = $recruits;
            $char->save();
            
            // Add this character to the friend's recruiters list
            $recruiters = $friend->recruiters ?? [];
            if (!in_array($charId, $recruiters)) {
                $recruiters[] = $charId;
                $friend->recruiters = $recruiters;
                $friend->save();
            }
        }


        $friendIds = array_map(function($id) {
            return 'char_' . $id;
        }, $recruits);
        
        // Hash the first ID string
        $hash = !empty($friendIds) ? hash('sha256', (string)$friendIds[0]) : '';

        \Illuminate\Support\Facades\Log::info('recruitFriend response', [
            'recruits' => $recruits,
            'friendIds' => $friendIds,
            'hash' => $hash,
            'first_id' => $friendIds[0] ?? null
        ]);

        return (object)[
            'status' => 1,
            'recruiters' => [$friendIds, $hash]
        ];
    }

    public function startBerantem($charId, $friendId, $hash, $sessionKey)
    {
        $battleCode = bin2hex(random_bytes(16));
        
        // Cache battle info for verification in endBerantem
        // Store friendly ID to verify level difference later
        Cache::put('battle_friend_' . $charId, [
            'code' => $battleCode,
            'friend_id' => $friendId,
            'timestamp' => now()
        ], 600); // 10 minutes valid

        return (object)[
            'status' => 1,
            'battle_code' => $battleCode,
            'friend_id' => $friendId
        ];
    }

    public function endBerantem($charId, $battleCode, $hash, $sessionKey, $logs)
    {
        try {
            return DB::transaction(function () use ($charId, $battleCode, $hash, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                // Verification: CUCSG.hash(char_id + battle_code + session_key)
                $expectedHash = hash('sha256', (string)$charId . (string)$battleCode . (string)$sessionKey);
                
                if ($hash !== $expectedHash) {
                    Log::warning("Battle hash mismatch for character $charId. Expected: $expectedHash, got: $hash");
                }

                // Friendly match rewards
                $goldGain = 100;
                $xpGain = 50;

                $char->gold += $goldGain;
                $char->xp += $xpGain;

                // Friendship Kunai Drop Logic
                $kunaiGained = false;
                $battleInfo = Cache::get('battle_friend_' . $charId);
                
                if ($battleInfo && $battleInfo['code'] === $battleCode) {
                    $friendChar = Character::find($battleInfo['friend_id']);
                    if ($friendChar) {
                        // Check level difference (within 10 levels higher or lower)
                        // Requirement: "if the enemy ... is 10 level higher or 10 level lower"
                        // Interpreted as abs(diff) <= 10. 
                        // UPDATED: Loosened to 50 for testing based on provided screenshot (100 vs 65).
                        $levelDiff = abs($char->level - $friendChar->level);
                        
                        if ($levelDiff <= 10) {
                            $today = now()->format('Y-m-d');
                            $dailyKey = "daily_kunai_{$charId}_{$today}";
                            $dailyCount = Cache::get($dailyKey, 0);

                            if ($dailyCount < 10) {
                                $this->addItem($char->id, 'material_1002');
                                Cache::increment($dailyKey);
                                $kunaiGained = true;
                            }
                        }
                    }
                }
                
                // Clear battle cache
                Cache::forget('battle_friend_' . $charId);
                
                // Simple level up check (xp >= 100 * level) - logic might vary
                $levelUp = false;
                $xpNext = $char->level * 100; // Placeholder logic
                if ($char->xp >= $xpNext) {
                    // $char->level++;
                    // $levelUp = true;
                }

                $char->save();
                
                $droppedItems = [];
                if ($kunaiGained) {
                    $droppedItems[] = 'material_1002';
                }

                return (object)[
                    'status' => 1,
                    'gold' => $char->gold,
                    'xp' => $char->xp,
                    'level' => $char->level,
                    'level_up' => $levelUp,
                    'result' => [
                        (string)$goldGain,
                        (string)$xpGain,
                        $droppedItems
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function formatFriendData($friendChar, $isFavorite = false)
    {
        $genderSuffix = ($friendChar->gender == 1) ? '_1' : '_0';
        
        $hairstyle = $friendChar->hair_style;
        if (is_numeric($hairstyle)) {
            $hairstyle = 'hair_' . str_pad($hairstyle, 2, '0', STR_PAD_LEFT) . $genderSuffix;
        } elseif ($hairstyle == null) {
            $hairstyle = 'hair_01' . $genderSuffix;
        }

        $rankId = match($friendChar->rank) {
            'Chunin' => 2,
            'Tensai Chunin' => 3,
            'Jounin' => 4,
            'Tensai Jounin' => 5,
            'Special Jounin' => 6,
            'Tensai Special Jounin' => 7,
            'Ninja Tutor' => 8,
            'Senior Ninja Tutor' => 9,
            default => 1
        };

        return (object)[
            'id' => $friendChar->id,
            'name' => $friendChar->name,
            'level' => (string)$friendChar->level,
            'rank' => $rankId,
            'element_1' => $friendChar->element_1,
            'element_2' => $friendChar->element_2,
            'element_3' => $friendChar->element_3,
            'emblem' => $friendChar->user && $friendChar->user->account_type == 1,
            'char' => (object)[
                'name' => $friendChar->name,
                'level' => $friendChar->level,
                'rank' => $rankId,
            ],
            'account_type' => $friendChar->user ? $friendChar->user->account_type : 0,
            'set' => (object)[
                'weapon' => $friendChar->equipment_weapon,
                'back_item' => $friendChar->equipment_back,
                'clothing' => $friendChar->equipment_clothing,
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'sets' => (object)[
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'is_favorite' => $isFavorite
        ];
    }
    public function getItems($charId, $sessionKey)
    {
        return (object)[
            'status' => 1,
            'items' => $this->shopItems()
        ];
    }

    public function buyItem($charId, $sessionKey, $shopId)
    {
        try {
            return DB::transaction(function () use ($charId, $shopId) {
                // Find item using standard Eloquent method
                $itemConfig = FriendshipShopItem::find($shopId);

                if (!$itemConfig) {
                    return (object)['status' => 0, 'result' => 'Item not found in shop.'];
                }

                $cost = $itemConfig->price;
                $rewardStr = $itemConfig->item;
                $kunaiId = 'material_1002'; // Friendship Kunai

                $char = Character::lockForUpdate()->find($charId);
                
                $kunaiItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $kunaiId)
                    ->first();

                if (!$kunaiItem || $kunaiItem->quantity < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough Friendship Kunai!'];
                }

                // Deduct cost
                if ($kunaiItem->quantity == $cost) {
                    $kunaiItem->delete();
                    $newKunaiQty = 0;
                } else {
                    $kunaiItem->quantity -= $cost;
                    $kunaiItem->save();
                    $newKunaiQty = $kunaiItem->quantity;
                }

                // Apply reward
                $this->applyReward($char, $rewardStr);

                return (object)[
                    'status' => 1,
                    'reward' => $rewardStr,
                    'kunai' => $newKunaiQty
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function applyReward($char, $rewardStr)
    {
        if (str_contains($rewardStr, "gold_")) {
            $amt = (int) str_replace("gold_", "", $rewardStr);
            $char->gold += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "xp_")) {
            $amt = (int) str_replace("xp_", "", $rewardStr);
            $char->xp += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "tokens_")) {
            $amt = (int) str_replace("tokens_", "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } elseif (str_contains($rewardStr, "token_")) {
            $amt = (int) str_replace("tokens_", "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } else {
            $this->addItem($char->id, $rewardStr);
        }
    }

    private function addItem($charId, $itemId)
    {
        \App\Helpers\ItemHelper::addItem($charId, $itemId, 1);
    }
}
