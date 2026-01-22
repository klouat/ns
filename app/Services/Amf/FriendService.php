<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FriendService
{
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

        return [
            'status' => 1,
            'friends' => $friendList,
            'page' => [
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'limit' => 100, // Max friends limit
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

        return [
            'status' => 1,
            'friends' => $friendList,
            'page' => [
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

        return [
            'status' => 1,
            'invitations' => $invitations,
            'page' => [
                'current' => (int)$page,
                'total' => ceil($total / $limit) ?: 1
            ],
            'total' => $total
        ];
    }

    public function addFriend($charId, $sessionKey, $targetFriendId)
    {
        if ($charId == $targetFriendId) {
            return ['status' => 0, 'result' => 'You cannot add yourself as a friend.'];
        }

        $existing = Friend::where('character_id', $charId)
            ->where('friend_id', $targetFriendId)
            ->first();

        if ($existing) {
            if ($existing->status == 1) {
                return ['status' => 0, 'result' => 'Already friends.'];
            } else {
                return ['status' => 0, 'result' => 'Friend request already sent.'];
            }
        }

        Friend::create([
            'character_id' => $charId,
            'friend_id' => $targetFriendId,
            'status' => 0
        ]);

        return [
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
            return ['status' => 0, 'result' => 'Friend request not found.'];
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

        return [
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

        return [
            'status' => 1,
            'result' => 'Friend removed.'
        ];
    }

    public function recruitable($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'result' => 'Character not found'];

        $char->is_recruitable = !$char->is_recruitable;
        $char->save();

        return [
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

        if (!$friend) return ['status' => 0, 'result' => 'Friend not found.'];

        $friend->is_favorite = true;
        $friend->save();

        return [
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

        if (!$friend) return ['status' => 0, 'result' => 'Friend not found.'];

        $friend->is_favorite = false;
        $friend->save();

        return [
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

        return [
            'status' => 1,
            'recommendations' => $recommendations,
            'page' => [
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

        return [
            'status' => 1,
            'friends' => $friendList,
            'page' => [
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

        return [
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

        return [
            'status' => 1,
            'result' => 'All friend requests accepted!'
        ];
    }

    public function removeAll($charId, $sessionKey)
    {
        Friend::where('friend_id', $charId)
            ->where('status', 0)
            ->delete();

        return [
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
            return ['status' => 2, 'result' => 'You can only recruit friends!'];
        }

        // 2. Check if recruitable
        $friend = Character::find($friendId);
        if (!$friend || !$friend->is_recruitable) {
             return ['status' => 2, 'result' => 'This friend is not recruitable right now.'];
        }

        // 3. Store in cache for Mission Room persistence
        $recruits = Cache::get('character_recruits_' . $charId, []);
        if (!in_array($friendId, $recruits)) {
            if (count($recruits) >= 2) {
                // Max 2 friends can be recruited in Ninja Saga
                array_shift($recruits);
            }
            $recruits[] = $friendId;
            Cache::put('character_recruits_' . $charId, $recruits, 3600);
        }

        // The client expects 'recruiters' => [ [id1, id2, ...], hash ]
        // The hash is CUCSG.hash(id1), which is SHA-256
        
        $friendIds = array_map(function($id) { return 'char_' . $id; }, $recruits);
        $hash = hash('sha256', (string)$friendIds[0]);

        return [
            'status' => 1,
            'recruiters' => [$friendIds, $hash]
        ];
    }

    public function startBerantem($charId, $friendId, $hash, $sessionKey)
    {
        // Simple implementation for character-vs-character battle
        return [
            'status' => 1,
            'battle_code' => bin2hex(random_bytes(16)),
            'friend_id' => $friendId
        ];
    }

    public function endBerantem($charId, $battleCode, $hash, $sessionKey, $logs)
    {
        try {
            return DB::transaction(function () use ($charId, $battleCode, $hash, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

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
                
                // Simple level up check (xp >= 100 * level) - logic might vary
                $levelUp = false;
                $xpNext = $char->level * 100; // Placeholder logic
                if ($char->xp >= $xpNext) {
                    // $char->level++;
                    // $levelUp = true;
                }

                $char->save();

                return [
                    'status' => 1,
                    'gold' => $char->gold,
                    'xp' => $char->xp,
                    'level' => $char->level,
                    'level_up' => $levelUp,
                    'result' => [
                        (string)$goldGain,
                        (string)$xpGain,
                        'Friendly Match Victory!'
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return ['status' => 0, 'error' => 'Internal Server Error'];
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

        return [
            'id' => $friendChar->id,
            'name' => $friendChar->name,
            'level' => (string)$friendChar->level,
            'rank' => $rankId,
            'element_1' => $friendChar->element_1,
            'element_2' => $friendChar->element_2,
            'element_3' => $friendChar->element_3,
            'emblem' => $friendChar->user && $friendChar->user->account_type == 1,
            'char' => [
                'name' => $friendChar->name,
                'level' => $friendChar->level,
                'rank' => $rankId,
            ],
            'account_type' => $friendChar->user ? $friendChar->user->account_type : 0,
            'set' => [
                'weapon' => $friendChar->equipment_weapon,
                'back_item' => $friendChar->equipment_back,
                'clothing' => $friendChar->equipment_clothing,
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'sets' => [
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'is_favorite' => $isFavorite
        ];
    }
}
