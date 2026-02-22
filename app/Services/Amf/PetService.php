<?php

namespace App\Services\Amf;

use App\Services\Amf\PetService\GetPetsService;
use App\Services\Amf\PetService\EquipPetService;
use App\Services\Amf\PetService\UnequipPetService;
use App\Services\Amf\PetService\LearnPetSkillService;
use App\Services\Amf\PetService\BuyPetService;
use App\Services\Amf\PetService\RenamePetService;

class PetService
{
    private GetPetsService $getPetsService;
    private EquipPetService $equipPetService;
    private UnequipPetService $unequipPetService;
    private LearnPetSkillService $learnPetSkillService;
    private BuyPetService $buyPetService;
    private RenamePetService $renamePetService;

    public function __construct()
    {
        $this->getPetsService = new GetPetsService();
        $this->equipPetService = new EquipPetService();
        $this->unequipPetService = new UnequipPetService();
        $this->learnPetSkillService = new LearnPetSkillService();
        $this->buyPetService = new BuyPetService();
        $this->renamePetService = new RenamePetService();
    }

    public function executeService($action, $params)
    {
        switch ($action) {
            case 'getPets':
                return $this->getPetsService->getPets($params);
            case 'equipPet':
                return $this->equipPetService->equipPet($params);
            case 'unequipPet':
                return $this->unequipPetService->unequipPet($params);
            case 'learnSkill':
                return $this->learnPetSkillService->learnSkill($params);
            case 'buyPet':
                return $this->buyPetService->buyPet($params);
            default:
                return (object)['status' => 0, 'error' => 'Action not found: ' . $action];
        }
    }

    public function renamePet($charId, $sessionKey, $petId, $newName)
    {
        return $this->renamePetService->renamePet($charId, $sessionKey, $petId, $newName);
    }
}
