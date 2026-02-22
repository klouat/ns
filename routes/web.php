<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AmfController;
use App\Http\Controllers\AdminPanelController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/server-time', function () {
    return view('panel.server_time', [
        'server_time' => now(),
    ]);
})->name('panel.server_time');

Route::get('/api/id-custom-color', function () {
    $characters = \App\Models\Character::whereNotNull('name_color')->get();
    $data = [];
    foreach ($characters as $char) {
        $data[$char->id] = $char->name_color;
    }
    return response()->json(['data' => $data]);
});

// AMF Gateway Routes
Route::any('/amf', [AmfController::class, 'handle']);
Route::any('/gateway.php', [AmfController::class, 'handle']);

// --------------------------------------------------------------------------
// Admin Panel Routes
// --------------------------------------------------------------------------

// 1. Character Management (New)
Route::get('/panel/characters', [AdminPanelController::class, 'characters'])->name('panel.characters');
Route::post('/panel/characters/update', [AdminPanelController::class, 'updateCharacter'])->name('panel.characters.update');

// 2. Skills Management
Route::get('/panel/skills', [AdminPanelController::class, 'skills'])->name('panel.skills');
Route::post('/panel/skills', [AdminPanelController::class, 'addSkill'])->name('panel.skills.add');
Route::post('/panel/skills/all', [AdminPanelController::class, 'addAllSkills'])->name('panel.skills.add.all');

// 3. Items Management
Route::get('/panel/items', [AdminPanelController::class, 'items'])->name('panel.items');
Route::post('/panel/items', [AdminPanelController::class, 'addItem'])->name('panel.items.add');
Route::post('/panel/items/all', [AdminPanelController::class, 'addAllItems'])->name('panel.items.add.all');

// 4. Mail System
Route::get('/panel/mail', [AdminPanelController::class, 'mail'])->name('panel.mail');
Route::post('/panel/mail', [AdminPanelController::class, 'sendMail'])->name('panel.mail.send');

// 5. Limited Store
Route::get('/panel/limited-store', [AdminPanelController::class, 'limitedStore'])->name('panel.limited_store');
Route::post('/panel/limited-store', [AdminPanelController::class, 'addLimitedStoreGroup'])->name('panel.limited_store.add');
Route::post('/panel/limited-store/update', [AdminPanelController::class, 'updateLimitedStoreGroup'])->name('panel.limited_store.update');
Route::delete('/panel/limited-store/{groupId}', [AdminPanelController::class, 'deleteLimitedStoreGroup'])->name('panel.limited_store.delete');

// 6. Hunting House
Route::get('/panel/hunting-house', [AdminPanelController::class, 'huntingHouse'])->name('panel.hunting_house');
Route::post('/panel/hunting-house', [AdminPanelController::class, 'addHuntingHouseItem'])->name('panel.hunting_house.add');
Route::post('/panel/hunting-house/update', [AdminPanelController::class, 'updateHuntingHouseItem'])->name('panel.hunting_house.update');
Route::delete('/panel/hunting-house/{id}', [AdminPanelController::class, 'deleteHuntingHouseItem'])->name('panel.hunting_house.delete');

// 7. Material Market
Route::get('/panel/material-market', [AdminPanelController::class, 'materialMarket'])->name('panel.material_market');
Route::post('/panel/material-market', [AdminPanelController::class, 'addMaterialMarketItem'])->name('panel.material_market.add');
Route::post('/panel/material-market/update', [AdminPanelController::class, 'updateMaterialMarketItem'])->name('panel.material_market.update');
Route::delete('/panel/material-market/{id}', [AdminPanelController::class, 'deleteMaterialMarketItem'])->name('panel.material_market.delete');

// 8. Friendship Shop
Route::get('/panel/friendship-shop', [AdminPanelController::class, 'friendshipShop'])->name('panel.friendship_shop');
Route::post('/panel/friendship-shop', [AdminPanelController::class, 'addFriendshipShopItem'])->name('panel.friendship_shop.add');
Route::post('/panel/friendship-shop/update', [AdminPanelController::class, 'updateFriendshipShopItem'])->name('panel.friendship_shop.update');
Route::delete('/panel/friendship-shop/{id}', [AdminPanelController::class, 'deleteFriendshipShopItem'])->name('panel.friendship_shop.delete');

// 9. Crew System Routes
use App\Http\Controllers\CrewController;

// 10. Special Deals
Route::get('/panel/special-deals', [AdminPanelController::class, 'specialDeals'])->name('panel.special_deals');
Route::post('/panel/special-deals', [AdminPanelController::class, 'addSpecialDeal'])->name('panel.special_deals.add');
Route::delete('/panel/special-deals/{id}', [AdminPanelController::class, 'deleteSpecialDeal'])->name('panel.special_deals.delete');

// 11. Giveaways
Route::get('/panel/giveaways', [AdminPanelController::class, 'giveaways'])->name('panel.giveaways');
Route::post('/panel/giveaways', [AdminPanelController::class, 'addGiveaway'])->name('panel.giveaways.add');
Route::delete('/panel/giveaways/{id}', [AdminPanelController::class, 'deleteGiveaway'])->name('panel.giveaways.delete');

// 12. Daily Rewards
Route::get('/panel/daily-rewards', [AdminPanelController::class, 'dailyRewards'])->name('panel.daily_rewards');
Route::post('/panel/daily-rewards', [AdminPanelController::class, 'updateDailyReward'])->name('panel.daily_rewards.update');
Route::delete('/panel/daily-rewards/{id}', [AdminPanelController::class, 'deleteDailyReward'])->name('panel.daily_rewards.delete');

// 9. Crew System Routes (moved down)

Route::prefix('crew')->group(function () {
    Route::post('/season', [CrewController::class, 'getSeason']);
    Route::post('/season/pool', [CrewController::class, 'getTokenPool']);
    Route::post('/auth/login', [CrewController::class, 'login']);
    
    Route::post('/player/crew', [CrewController::class, 'getCrewData']);
    Route::post('/player/stamina', [CrewController::class, 'getStamina']);
    Route::post('/history', [CrewController::class, 'getHistory']);
    
    Route::post('/battle/opponents', [CrewController::class, 'getCrewsForBattle']);
    Route::post('/battle/opponents/{id}', [CrewController::class, 'searchCrewsForBattle']);
    
    Route::post('/request/available', [CrewController::class, 'getCrewsForRequest']);
    Route::post('/request/available/{id}', [CrewController::class, 'searchCrewsForRequest']);
    Route::post('/player/request/{id}', [CrewController::class, 'sendRequestToCrew']);
    
    Route::post('/player/crew/members', [CrewController::class, 'getMembersInfo']);
    Route::post('/request/all', [CrewController::class, 'getMemberRequests']);
    Route::post('/request/{id}/reject', [CrewController::class, 'rejectMember']);
    Route::post('/request/all/reject', [CrewController::class, 'rejectAllMembers']);
    Route::post('/request/{id}/accept', [CrewController::class, 'acceptMember']);
    
    Route::post('/player/quit', [CrewController::class, 'quitFromCrew']);
    Route::post('/player/kick/{id}', [CrewController::class, 'kickMember']);
    Route::post('/player/promote-elder/{id}', [CrewController::class, 'promoteElder']);
    Route::post('/player/switch-master/{id}', [CrewController::class, 'changeCrewMaster']);
    
    Route::post('/player/donate/{amount}/golds', [CrewController::class, 'donateGolds']);
    Route::post('/player/donate/{amount}/tokens', [CrewController::class, 'donateTokens']);
    
    Route::post('/upgrade/building/{id}', [CrewController::class, 'upgradeBuilding']);
    Route::post('/announcements', [CrewController::class, 'updateAnnouncement']);
    Route::post('/announcement/publish', [CrewController::class, 'publishAnnouncement']);
    Route::post('/upgrade/max-members', [CrewController::class, 'increaseMaxMembers']);
    
    Route::post('/player/stamina/upgrade-max', [CrewController::class, 'upgradeMaxStamina']);
    Route::post('/player/boost-prestige', [CrewController::class, 'boostPrestige']);
    Route::post('/player/stamina/refill', [CrewController::class, 'restoreStamina']);
    
    Route::post('/battle/castles/{id}/ranks', [CrewController::class, 'getCrewRanks']);
    Route::post('/battle/castles/{id}/recovery', [CrewController::class, 'getRecoverLifeBar']);
    Route::post('/battle/castles/{id}/recover', [CrewController::class, 'recoverCastle']);
    Route::post('/battle/castles/{id}/defenders', [CrewController::class, 'getDefenders']);
    
    // Route for recruitment popup (ClanRecruitManual.as calls /battle/defenders without ID)
    Route::post('/battle/defenders', [CrewController::class, 'getRecruits']);
    
    Route::post('/battle/role/switch/{id}', [CrewController::class, 'switchRole']);
    Route::post('/battle/attackers', [CrewController::class, 'getAttackers']);
    Route::post('/battle/castles/{id?}', [CrewController::class, 'getCastles']);
    
    Route::post('/battle/phase{phase}/start', [CrewController::class, 'startBattle']);
    Route::post('/battle/phase{phase}/finish', [CrewController::class, 'finishBattle']);
    
    Route::post('/create', [CrewController::class, 'createCrew']);
    Route::post('/rename', [CrewController::class, 'renameCrew']);
    
    Route::post('/player/buy-onigiri/{id}', [CrewController::class, 'buyOnigiriPackage']);
    Route::post('/member/{id}/onigiri/limit', [CrewController::class, 'getOnigiriInfo']);
    Route::post('/member/{id}/onigiri/gift/{amount}', [CrewController::class, 'giveOnigiri']);
    Route::post('/request/{id}/invite', [CrewController::class, 'inviteCharacter']);
    
    Route::post('/season-histories', [CrewController::class, 'seasonHistories']);
    Route::post('/season/previous', [CrewController::class, 'getLastSeasonRewards']);
    
    Route::post('/player/minigame', [CrewController::class, 'getMiniGame']);
    Route::post('/player/minigame/start', [CrewController::class, 'startMiniGame']);
    Route::post('/player/minigame/finish', [CrewController::class, 'finishMiniGame']);
    Route::post('/player/minigame/buy/{id}', [CrewController::class, 'buyMiniGame']);
});