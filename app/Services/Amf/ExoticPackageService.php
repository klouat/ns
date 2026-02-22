<?php

namespace App\Services\Amf;

use App\Services\Amf\ExoticPackageService\PackageService;

class ExoticPackageService
{
    private PackageService $packageService;

    public function __construct()
    {
        $this->packageService = new PackageService();
    }

    public function get($charId, $sessionKey)
    {
        return $this->packageService->get($charId, $sessionKey);
    }

    public function buy($charId, $sessionKey, $packageId)
    {
        return $this->packageService->buy($charId, $sessionKey, $packageId);
    }
}
