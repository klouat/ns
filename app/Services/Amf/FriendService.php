<?php

namespace App\Services\Amf;

use App\Services\Amf\FriendService\FriendHelperService;
use App\Services\Amf\FriendService\ManagementService;
use App\Services\Amf\FriendService\SearchService;
use App\Services\Amf\FriendService\RecruitService;
use App\Services\Amf\FriendService\BattleService;
use App\Services\Amf\FriendService\ShopService;

class FriendService
{
    private ManagementService $management;
    private SearchService $search;
    private RecruitService $recruit;
    private BattleService $battle;
    private ShopService $shop;

    public function __construct()
    {
        $helper = new FriendHelperService();
        $this->management = new ManagementService($helper);
        $this->search = new SearchService($helper);
        $this->recruit = new RecruitService();
        $this->battle = new BattleService();
        $this->shop = new ShopService();
    }

    public function friends($charId, $sessionKey, $page = 1)
    {
        return $this->management->friends($charId, $sessionKey, $page);
    }

    public function getFavorite($charId, $sessionKey, $page = 1)
    {
        return $this->management->getFavorite($charId, $sessionKey, $page);
    }

    public function friendRequests($charId, $sessionKey, $page = 1)
    {
        return $this->management->friendRequests($charId, $sessionKey, $page);
    }

    public function addFriend($charId, $sessionKey, $targetFriendId)
    {
        return $this->management->addFriend($charId, $sessionKey, $targetFriendId);
    }

    public function acceptFriend($charId, $sessionKey, $requesterId)
    {
        return $this->management->acceptFriend($charId, $sessionKey, $requesterId);
    }

    public function removeFriend($charId, $sessionKey, $friendId)
    {
        return $this->management->removeFriend($charId, $sessionKey, $friendId);
    }

    public function setFavorite($charId, $sessionKey, $friendId)
    {
        return $this->management->setFavorite($charId, $sessionKey, $friendId);
    }

    public function removeFavorite($charId, $sessionKey, $friendId)
    {
        return $this->management->removeFavorite($charId, $sessionKey, $friendId);
    }

    public function unfriendAll($charId, $sessionKey)
    {
        return $this->management->unfriendAll($charId, $sessionKey);
    }

    public function acceptAll($charId, $sessionKey)
    {
        return $this->management->acceptAll($charId, $sessionKey);
    }

    public function removeAll($charId, $sessionKey)
    {
        return $this->management->removeAll($charId, $sessionKey);
    }

    public function getRecommendations($charId, $sessionKey, $page = 1)
    {
        return $this->search->getRecommendations($charId, $sessionKey, $page);
    }

    public function search($charId, $sessionKey, $query, $page = 1)
    {
        return $this->search->search($charId, $sessionKey, $query, $page);
    }

    public function recruitable($charId, $sessionKey)
    {
        return $this->recruit->recruitable($charId, $sessionKey);
    }

    public function recruitFriend($charId, $sessionKey, $friendId)
    {
        return $this->recruit->recruitFriend($charId, $sessionKey, $friendId);
    }

    public function startBerantem($charId, $friendId, $hash, $sessionKey)
    {
        return $this->battle->startBerantem($charId, $friendId, $hash, $sessionKey);
    }

    public function endBerantem($charId, $battleCode, $hash, $sessionKey, $logs)
    {
        return $this->battle->endBerantem($charId, $battleCode, $hash, $sessionKey, $logs);
    }

    public function getItems($charId, $sessionKey)
    {
        return $this->shop->getItems($charId, $sessionKey);
    }

    public function buyItem($charId, $sessionKey, $shopId)
    {
        return $this->shop->buyItem($charId, $sessionKey, $shopId);
    }
}
