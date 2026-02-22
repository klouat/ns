<?php

namespace App\Services\Amf;

use App\Services\Amf\MysteriousMarketService\GetPackageService;
use App\Services\Amf\MysteriousMarketService\BuyPackageService;
use App\Services\Amf\MysteriousMarketService\GetAllPackagesService;
use App\Services\Amf\MysteriousMarketService\RefreshPackageService;

class MysteriousMarketService
{
    private GetPackageService $getPackageService;
    private BuyPackageService $buyPackageService;
    private GetAllPackagesService $getAllPackagesService;
    private RefreshPackageService $refreshPackageService;

    public function __construct()
    {
        $this->getPackageService = new GetPackageService();
        $this->buyPackageService = new BuyPackageService();
        $this->getAllPackagesService = new GetAllPackagesService();
        $this->refreshPackageService = new RefreshPackageService();
    }

    public function getPackageData($charId, $sessionKey)
    {
        return $this->getPackageService->getPackageData($charId, $sessionKey);
    }

    public function buyPackage($charId, $sessionKey, $selectedSkillId)
    {
        return $this->buyPackageService->buyPackage($charId, $sessionKey, $selectedSkillId);
    }

    public function getAllPackagesList($charId, $sessionKey)
    {
        return $this->getAllPackagesService->getAllPackagesList($charId, $sessionKey);
    }

    public function refreshPackage($charId, $sessionKey)
    {
        return $this->refreshPackageService->refreshPackage($charId, $sessionKey);
    }
}
