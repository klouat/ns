<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Amf\CrewService;

class CrewController extends Controller
{
    protected $crewService;

    public function __construct(CrewService $crewService)
    {
        $this->crewService = $crewService;
    }

    // Helper to extract charId and sessionKey from request
    private function getAuthParams(Request $request)
    {
        // First try POST body (used by login endpoint)
        $charId = $request->input('char_id') ?? $request->input('charId');
        $sessionKey = $request->input('session_key') ?? $request->input('sessionKey');
        
        // If not in body, try Authorization header (used by authenticated endpoints)
        if (!$charId || !$sessionKey) {
            $authHeader = $request->header('Authorization');
            
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7); // Remove "Bearer " prefix
                $sessionKey = $token;
                
                // Try to get char_id from cache (stored during login)
                $charId = \Cache::get('crew_token_' . $token);
                
                \Log::info('Extracted from Bearer token', [
                    'token' => substr($token, 0, 20) . '...',
                    'char_id' => $charId
                ]);
            }
        }
        
        return [$charId, $sessionKey];
    }

    public function getSeason(Request $request)
    {
        return response()->json($this->crewService->getSeason());
    }

    public function getTokenPool(Request $request)
    {
        return response()->json($this->crewService->getTokenPool());
    }

    public function login(Request $request)
    {
        \Log::info('Crew login attempt', [
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);
        
        [$charId, $sessionKey] = $this->getAuthParams($request);
        
        \Log::info('Crew login params extracted', [
            'char_id' => $charId,
            'session_key' => $sessionKey
        ]);
        
        $result = $this->crewService->login($charId, $sessionKey);
        
        // Store char_id in cache for later retrieval (24 hours)
        if (isset($result->access_token)) {
            \Cache::put('crew_token_' . $result->access_token, $charId, now()->addDay());
            \Log::info('Stored char_id in cache', [
                'token' => substr($result->access_token, 0, 20) . '...',
                'char_id' => $charId
            ]);
        }
        
        return response()->json($result);
    }
    
    public function getCrewData(Request $request)
    {
        \Log::info('getCrewData controller called', [
            'all_data' => $request->all(),
            'json' => $request->json()->all(),
            'input' => $request->input(),
        ]);
        
        [$charId, $sessionKey] = $this->getAuthParams($request);
        
        \Log::info('getCrewData params extracted', [
            'char_id' => $charId,
            'session_key' => $sessionKey
        ]);
        
        return response()->json($this->crewService->getCrewData($charId, $sessionKey));
    }

    public function getStamina(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getStamina($charId, $sessionKey));
    }

    public function getHistory(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getHistory($charId, $sessionKey));
    }
    
    public function getCrewsForBattle(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getCrewsForBattle($charId, $sessionKey));
    }


    public function searchCrewsForBattle(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->searchCrewsForBattle($charId, $sessionKey, $id));
    }

    
    public function getCrewsForRequest(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getCrewsForRequest($charId, $sessionKey));
    }

    public function searchCrewsForRequest(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->searchCrewsForRequest($charId, $sessionKey, $id));
    }

    public function sendRequestToCrew(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->sendRequestToCrew($charId, $sessionKey, $id));
    }
    
    public function getMembersInfo(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getMembersInfo($charId, $sessionKey));
    }

    public function getMemberRequests(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getMemberRequests($charId, $sessionKey));
    }

    public function rejectMember(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->rejectMember($charId, $sessionKey, $id));
    }

    public function rejectAllMembers(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->rejectMembers($charId, $sessionKey));
    }

    public function acceptMember(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->acceptMember($charId, $sessionKey, $id));
    }
    
    public function quitFromCrew(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->quitFromCrew($charId, $sessionKey));
    }

    public function kickMember(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->kickMember($charId, $sessionKey, $id));
    }

    public function promoteElder(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->promoteElder($charId, $sessionKey, $id));
    }

    public function changeCrewMaster(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->changeCrewMaster($charId, $sessionKey, $id));
    }
    
    public function donateGolds(Request $request, $amount)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->donateGolds($charId, $sessionKey, $amount));
    }

    public function donateTokens(Request $request, $amount)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->donateTokens($charId, $sessionKey, $amount));
    }
    
    public function upgradeBuilding(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->upgradeBuilding($charId, $sessionKey, $id));
    }

    public function updateAnnouncement(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $text = $request->input('announcement') ?? $request->input('text') ?? '';
        return response()->json($this->crewService->updateAnnouncement($charId, $sessionKey, $text));
    }

    public function publishAnnouncement(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->publishAnnouncement($charId, $sessionKey));
    }

    public function increaseMaxMembers(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->increaseMaxMembers($charId, $sessionKey));
    }
    
    public function upgradeMaxStamina(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->upgradeMaxStamina($charId, $sessionKey));
    }

    public function boostPrestige(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->boostPrestige($charId, $sessionKey));
    }

    public function restoreStamina(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->restoreStamina($charId, $sessionKey));
    }
    
    public function getCrewRanks(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getCrewRanks($charId, $sessionKey, $id));
    }

    public function getRecoverLifeBar(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getRecoverLifeBar($charId, $sessionKey, $id));
    }

    public function recoverCastle(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->recoverCastle($charId, $sessionKey, $id));
    }

    public function getDefenders(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getDefenders($charId, $sessionKey, $id));
    }
    
    // New method for recruitment GUI
    public function getRecruits(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getRecruits($charId, $sessionKey));
    }

    public function switchRole(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->switchRole($charId, $sessionKey, $id));
    }

    public function getAttackers(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getAttackers($charId, $sessionKey));
    }

    public function getCastles(Request $request, $id = null)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getCastles($charId, $sessionKey, $id));
    }
    
    public function startBattle(Request $request, $phase)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $data = $request->all();
        return response()->json($this->crewService->startBattle($charId, $sessionKey, $phase, $data));
    }

    public function finishBattle(Request $request, $phase)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $data = $request->all();
        return response()->json($this->crewService->finishBattle($charId, $sessionKey, $phase, $data));
    }
    
    public function createCrew(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $name = $request->input('name');
        return response()->json($this->crewService->createCrew($charId, $sessionKey, $name));
    }

    public function renameCrew(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $name = $request->input('name');
        return response()->json($this->crewService->renameCrew($charId, $sessionKey, $name));
    }
    
    public function buyOnigiriPackage(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->buyOnigiriPackage($charId, $sessionKey, $id));
    }

    public function getOnigiriInfo(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getOnigiriInfo($charId, $sessionKey, $id));
    }

    public function giveOnigiri(Request $request, $id, $amount)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->giveOnigiri($charId, $sessionKey, $id, $amount));
    }

    public function inviteCharacter(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->inviteCharacter($charId, $sessionKey, $id));
    }
    
    public function seasonHistories(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->seasonHistories($charId, $sessionKey));
    }

    public function getLastSeasonRewards(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getLastSeasonRewards($charId, $sessionKey));
    }
    
    public function getMiniGame(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->getMiniGame($charId, $sessionKey));
    }

    public function startMiniGame(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->startMiniGame($charId, $sessionKey));
    }

    public function finishMiniGame(Request $request)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        $data = $request->all();
        return response()->json($this->crewService->finishMiniGame($charId, $sessionKey, $data));
    }

    public function buyMiniGame(Request $request, $id)
    {
        [$charId, $sessionKey] = $this->getAuthParams($request);
        return response()->json($this->crewService->buyMiniGame($charId, $sessionKey, $id));
    }
}
