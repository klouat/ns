<?php

namespace App\Services\Amf;

use App\Services\Amf\SystemLoginService\VersionService;
use App\Services\Amf\SystemLoginService\RegisterService;
use App\Services\Amf\SystemLoginService\LoginService;
use App\Services\Amf\SystemLoginService\CharacterDataService;
use App\Services\Amf\SystemLoginService\AccountService;

class SystemLoginService
{
    private VersionService $versionService;
    private RegisterService $registerService;
    private LoginService $loginService;
    private CharacterDataService $characterDataService;
    private AccountService $accountService;

    public function __construct()
    {
        $this->versionService = new VersionService();
        $this->registerService = new RegisterService();
        $this->loginService = new LoginService();
        $this->characterDataService = new CharacterDataService();
        $this->accountService = new AccountService();
    }

    public function checkVersion($buildNum)
    {
        return $this->versionService->checkVersion($buildNum);
    }

    public function registerUser($username, $email, $password, $serverString)
    {
        return $this->registerService->registerUser($username, $email, $password, $serverString);
    }

    public function loginUser($username, $encryptedPassword, $char_, $bl, $bt, $char__, $item, $seed, $passLen)
    {
        return $this->loginService->loginUser($username, $encryptedPassword, $char_, $bl, $bt, $char__, $item, $seed, $passLen);
    }

    public function getCharacterData($charId, $sessionkey)
    {
        return $this->characterDataService->getCharacterData($charId, $sessionkey);
    }

    public function getAllCharacters($uid, $sessionkey)
    {
        return $this->accountService->getAllCharacters($uid, $sessionkey);
    }

    public function decryptPassword($encryptedBase64, $keyString, $ivString)
    {
        return $this->loginService->decryptPassword($encryptedBase64, $keyString, $ivString);
    }

    public function getTalentSkillsString($char)
    {
        return $this->characterDataService->getTalentSkillsString($char);
    }

    public function getSenjutsuSkillsString($char)
    {
        return $this->characterDataService->getSenjutsuSkillsString($char);
    }

    public function getEquippedPetData($char)
    {
        return $this->characterDataService->getEquippedPetData($char);
    }
}
