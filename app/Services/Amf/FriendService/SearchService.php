<?php

namespace App\Services\Amf\FriendService;

use App\Models\Character;
use App\Models\Friend;

class SearchService
{
    private FriendHelperService $helper;

    public function __construct(FriendHelperService $helper)
    {
        $this->helper = $helper;
    }

    public function getRecommendations($charId, $sessionKey, $page = 1)
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $alreadyFriends = Friend::where('character_id', $charId)->pluck('friend_id')->toArray();
        array_push($alreadyFriends, $charId);

        $recommendQuery = Character::whereNotIn('id', $alreadyFriends);
        
        $total = $recommendQuery->count();
        $recommendChars = $recommendQuery->offset($offset)->limit($limit)->get();

        $recommendations = [];
        foreach ($recommendChars as $c) {
            $recommendations[] = $this->helper->formatFriendData($c);
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

            $friendList[] = $this->helper->formatFriendData($c, $isFavorite);
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
}
