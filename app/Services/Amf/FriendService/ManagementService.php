<?php

namespace App\Services\Amf\FriendService;

use App\Models\Character;
use App\Models\Friend;
use Illuminate\Support\Facades\DB;

class ManagementService
{
    private FriendHelperService $helper;

    public function __construct(FriendHelperService $helper)
    {
        $this->helper = $helper;
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
                $friendList[] = $this->helper->formatFriendData($friendChar, $f->is_favorite);
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
                $friendList[] = $this->helper->formatFriendData($friendChar, true);
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
        $limit = 4;
        $offset = ($page - 1) * $limit;

        $requestsQuery = Friend::where('friend_id', $charId)
            ->where('status', 0);

        $total = $requestsQuery->count();
        $requests = $requestsQuery->offset($offset)->limit($limit)->get();

        $invitations = [];
        foreach ($requests as $r) {
            $requesterChar = Character::with('user')->find($r->character_id);
            if ($requesterChar) {
                $invitations[] = $this->helper->formatFriendData($requesterChar);
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
}
