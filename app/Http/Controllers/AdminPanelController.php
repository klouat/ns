<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\CharacterItem;
use App\Models\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\LimitedStoreItem;
use App\Models\HuntingHouseItem;
use App\Models\FriendshipShopItem;
use App\Models\MaterialMarketItem;
use App\Models\SpecialDeal;
use App\Models\Giveaway;
use App\Models\AttendanceReward;
use Illuminate\Support\Str;

class AdminPanelController extends Controller
{
    private function getSkillsData()
    {
        $path = storage_path('app/listskill.json');
        if (!File::exists($path)) {
            return [];
        }
        $json = File::get($path);
        return json_decode($json, true);
    }

    private function getItemsData()
    {
        // Using library.json as requested
        $path = storage_path('app/library.json');
        if (!File::exists($path)) {
            return [];
        }
        $json = File::get($path);
        $items = json_decode($json, true);

        if (!is_array($items)) {
            return [];
        }

        $allowedTypes = ['wpn', 'set', 'hair', 'back', 'accessory', 'item'];

        $filtered = array_filter($items, function ($item) use ($allowedTypes) {
            return isset($item['type']) && in_array($item['type'], $allowedTypes);
        });

        return array_values($filtered);
    }

    public function skills()
    {
        $characters = Character::all();
        $skills = $this->getSkillsData();
        
        // Sort skills by name for easier finding in dropdown
        usort($skills, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return view('panel.skills', compact('characters', 'skills'));
    }

    public function addSkill(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'skill_id' => 'required|string',
        ]);

        CharacterSkill::firstOrCreate([
            'character_id' => $request->character_id,
            'skill_id' => $request->skill_id,
        ]);

        return redirect()->back()->with('success', 'Skill added successfully!');
    }

    public function addAllSkills(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
        ]);

        $skills = $this->getSkillsData();
        $count = 0;

        foreach ($skills as $skill) {
            $exists = CharacterSkill::where('character_id', $request->character_id)
                ->where('skill_id', $skill['id'])
                ->exists();
            
            if (!$exists) {
                CharacterSkill::create([
                    'character_id' => $request->character_id,
                    'skill_id' => $skill['id'],
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Added {$count} new skills successfully!");
    }

    public function items()
    {
        $characters = Character::all();
        $items = $this->getItemsData();

        // Sort items by name
        usort($items, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return view('panel.items', compact('characters', 'items'));
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'item_id' => 'required|string',
        ]);

        // Find the item details to get the type/category
        $items = $this->getItemsData();
        $itemData = null;
        foreach ($items as $item) {
            if ($item['id'] == $request->item_id) {
                $itemData = $item;
                break;
            }
        }

        if (!$itemData) {
            return redirect()->back()->with('error', 'Item definition not found in JSON.');
        }

        // Map category
        $category = 'item';
        if (isset($itemData['type'])) {
            if ($itemData['type'] == 'wpn') $category = 'weapon';
            else $category = $itemData['type'];
        }

        $charItem = CharacterItem::where('character_id', $request->character_id)
            ->where('item_id', $request->item_id)
            ->first();

        if ($charItem) {
            $charItem->quantity += 1;
            $charItem->save();
        } else {
            CharacterItem::create([
                'character_id' => $request->character_id,
                'item_id' => $request->item_id,
                'quantity' => 1,
                'category' => $category,
            ]);
        }

        return redirect()->back()->with('success', 'Item added successfully!');
    }

    public function addAllItems(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
        ]);

        $items = $this->getItemsData();
        $count = 0;

        foreach ($items as $item) {
            // Map category
            $category = 'item';
            if (isset($item['type'])) {
                if ($item['type'] == 'wpn') $category = 'weapon';
                else $category = $item['type'];
            }

            $charItem = CharacterItem::where('character_id', $request->character_id)
                ->where('item_id', $item['id'])
                ->first();

            if (!$charItem) {
                CharacterItem::create([
                    'character_id' => $request->character_id,
                    'item_id' => $item['id'],
                    'quantity' => 1,
                    'category' => $category,
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Added {$count} new items successfully!");
    }

    public function mail()
    {
        $characters = Character::all();
        $items = $this->getItemsData();
        $skills = $this->getSkillsData();
        
        // Sort items by name
        usort($items, function ($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        // Sort skills by name
        usort($skills, function ($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return view('panel.mail', compact('characters', 'items', 'skills'));
    }

    public function sendMail(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|integer',
            'rewards' => 'nullable|string', // Comma separated rewards from builder
        ]);

        Mail::create([
            'character_id' => $request->character_id,
            'sender_name' => $request->sender_name ?: 'Admin',
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'rewards' => $request->rewards,
            'is_viewed' => false,
            'is_claimed' => false,
        ]);

        return redirect()->back()->with('success', 'Mail sent successfully with rewards: ' . ($request->rewards ?: 'None'));
    }

    // --- NEW CHARACTER MANAGEMENT METHODS ---
    // Note: These must be INSIDE the class closing bracket

    public function characters(Request $request)
    {
        // 1. Eager load 'user' so we can access $char->user->tokens
        $query = Character::with('user');

        // Search Logic
        if ($request->has('search') && $request->search != '') {
            $search = $request->get('search');
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('user_id', $search);
        }

        $characters = $query->orderBy('updated_at', 'desc')->paginate(10);
        
        return view('panel.characters', compact('characters'));
    }

    public function updateCharacter(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            // User Data Validation
            'tokens' => 'required|integer|min:0',
            'account_type' => 'required|integer|in:0,1', // 0=Free, 1=Emblem
            // Character Data Validation
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            'gold' => 'required|integer|min:0',
            'rank' => 'required|string',
            'gender' => 'required|integer',
            'xp' => 'nullable|integer',
            'tp' => 'nullable|integer',
            'character_ss' => 'nullable|integer|min:0',
        ]);

        $character = Character::findOrFail($request->character_id);

        // 1. Update The User (Tokens & Account Type)
        // Since multiple chars share one user, this updates it for all of them.
        if ($character->user) {
            $character->user->update([
                'tokens' => $request->tokens,
                'account_type' => $request->account_type,
            ]);
        }

        // 2. Update The Character
        $character->update([
            'name' => $request->name,
            'level' => $request->level,
            'gold' => $request->gold,
            'rank' => $request->rank,
            'gender' => $request->gender,
            'xp' => $request->xp ?? $character->xp,
            'tp' => $request->tp ?? $character->tp,
            'element_1' => $request->element_1,
            'element_2' => $request->element_2,
            'element_3' => $request->element_3,
            'point_fire' => $request->point_fire,
            'point_water' => $request->point_water,
            'point_wind' => $request->point_wind,
            'point_earth' => $request->point_earth,
            'point_lightning' => $request->point_lightning,
            'character_ss' => $request->character_ss ?? $character->character_ss,
        ]);

        return redirect()->route('panel.characters')->with('success', "Updated Character '{$character->name}' and User Account!");
    }

    public function limitedStore()
    {
        $items = LimitedStoreItem::orderBy('group_id')->orderBy('sort_order')->get();
        // Get generic list of skills from JSON for the dropdown
        $allSkills = $this->getSkillsData();
        return view('panel.limited_store', compact('items', 'allSkills'));
    }

    public function addLimitedStoreGroup(Request $request)
    {
        $request->validate([
            'base_skill_id' => 'required|string',
            'upgrade_skill_id' => 'required|string',
            'base_price' => 'required|integer',
            'upgrade_price' => 'required|integer',
            'group_name' => 'required|string',
        ]);

        // Create Group ID (slugify name or random)
        $groupId = 'group_' . Str::slug($request->group_name) . '_' . Str::random(5);

        // Add Base
        LimitedStoreItem::create([
            'item_id' => $request->base_skill_id,
            'price_token' => $request->base_price,
            'category' => 'skill',
            'group_id' => $groupId,
            'sort_order' => 1,
            'is_active' => true
        ]);

        // Add Upgrade
        LimitedStoreItem::create([
            'item_id' => $request->upgrade_skill_id,
            'price_token' => $request->upgrade_price,
            'category' => 'skill',
            'group_id' => $groupId,
            'sort_order' => 2,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Limited Store Group Added!');
    }

    public function updateLimitedStoreGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|string',
            'base_price' => 'required|integer',
            'upgrade_price' => 'required|integer',
        ]);

        // Update Base (Order 1)
        LimitedStoreItem::where('group_id', $request->group_id)
            ->where('sort_order', 1)
            ->update(['price_token' => $request->base_price]);

        // Update Upgrade (Order 2)
        LimitedStoreItem::where('group_id', $request->group_id)
            ->where('sort_order', 2)
            ->update(['price_token' => $request->upgrade_price]);

        return redirect()->back()->with('success', 'Group prices updated!');
    }

    public function deleteLimitedStoreGroup($groupId)
    {
        LimitedStoreItem::where('group_id', $groupId)->delete();
        return redirect()->back()->with('success', 'Group deleted!');
    }

    // --- HUNTING HOUSE ---
    public function huntingHouse()
    {
        $items = HuntingHouseItem::orderBy('sort_order')->paginate(20);
        $allItems = $this->getItemsData(); // For lookup
        $allSkills = $this->getSkillsData();
        return view('panel.hunting_house', compact('items', 'allItems', 'allSkills'));
    }

    public function addHuntingHouseItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'requirements' => 'required|string', // Format: item_id:qty, item_id:qty
            'category' => 'required|string',
        ]);

        $reqs = explode(',', $request->requirements);
        $materials = [];
        $quantities = [];

        foreach ($reqs as $req) {
            $parts = explode(':', trim($req));
            if (count($parts) == 2) {
                $materials[] = $parts[0];
                $quantities[] = intval($parts[1]);
            }
        }

        HuntingHouseItem::create([
            'item_id' => $request->item_id,
            'category' => $request->category,
            'materials' => $materials,
            'quantities' => $quantities,
            'sort_order' => HuntingHouseItem::max('sort_order') + 1,
        ]);

        return redirect()->back()->with('success', 'Hunting House Item Added!');
    }

    public function deleteHuntingHouseItem($id)
    {
        HuntingHouseItem::destroy($id);
        return redirect()->back()->with('success', 'Item deleted!');
    }

    // --- MATERIAL MARKET ---
    public function materialMarket()
    {
        $items = MaterialMarketItem::orderBy('sort_order')->paginate(20);
        $allItems = $this->getItemsData();
        $allSkills = $this->getSkillsData();
        return view('panel.material_market', compact('items', 'allItems', 'allSkills'));
    }

    public function addMaterialMarketItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'requirements' => 'required|string',
            'category' => 'required|string',
        ]);

        $reqs = explode(',', $request->requirements);
        $materials = [];
        $quantities = [];

        foreach ($reqs as $req) {
            $parts = explode(':', trim($req));
            if (count($parts) == 2) {
                $materials[] = $parts[0];
                $quantities[] = intval($parts[1]);
            }
        }

        MaterialMarketItem::create([
            'item_id' => $request->item_id,
            'category' => $request->category,
            'materials' => $materials,
            'quantities' => $quantities,
            'sort_order' => MaterialMarketItem::max('sort_order') + 1,
        ]);

        return redirect()->back()->with('success', 'Material Market Item Added!');
    }

    public function deleteMaterialMarketItem($id)
    {
        MaterialMarketItem::destroy($id);
        return redirect()->back()->with('success', 'Item deleted!');
    }

    // --- FRIENDSHIP SHOP ---
    public function friendshipShop()
    {
        $items = FriendshipShopItem::orderBy('price')->paginate(20);
        $allItems = $this->getItemsData();
        return view('panel.friendship_shop', compact('items', 'allItems'));
    }

    public function addFriendshipShopItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'price' => 'required|integer',
        ]);

        FriendshipShopItem::create([
            'item' => $request->item_id, // Note: field is 'item'
            'price' => $request->price,
        ]);

        return redirect()->back()->with('success', 'Friendship Shop Item Added!');
    }

    public function deleteFriendshipShopItem($id)
    {
        FriendshipShopItem::destroy($id);
        return redirect()->back()->with('success', 'Item deleted!');
    }

    // --- UPDATE METHODS ---

    public function updateHuntingHouseItem(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'requirements' => 'required|string',
        ]);

        $reqs = explode(',', $request->requirements);
        $materials = [];
        $quantities = [];

        foreach ($reqs as $req) {
            $parts = explode(':', trim($req));
            if (count($parts) == 2) {
                $materials[] = $parts[0];
                $quantities[] = intval($parts[1]);
            }
        }

        HuntingHouseItem::where('id', $request->id)->update([
            'materials' => $materials,
            'quantities' => $quantities,
        ]);

        return redirect()->back()->with('success', 'Exchange updated!');
    }

    public function updateMaterialMarketItem(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'requirements' => 'required|string',
        ]);

        $reqs = explode(',', $request->requirements);
        $materials = [];
        $quantities = [];

        foreach ($reqs as $req) {
            $parts = explode(':', trim($req));
            if (count($parts) == 2) {
                $materials[] = $parts[0];
                $quantities[] = intval($parts[1]);
            }
        }

        MaterialMarketItem::where('id', $request->id)->update([
            'materials' => $materials,
            'quantities' => $quantities,
        ]);

        return redirect()->back()->with('success', 'Exchange updated!');
    }

    public function updateFriendshipShopItem(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'price' => 'required|integer',
        ]);

        FriendshipShopItem::where('id', $request->id)->update([
            'price' => $request->price
        ]);

        return redirect()->back()->with('success', 'Item updated!');
    }

    // --- SPECIAL DEALS ---
    public function specialDeals()
    {
        $deals = SpecialDeal::orderBy('id', 'desc')->paginate(10);
        return view('panel.special_deals', compact('deals'));
    }

    public function addSpecialDeal(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'rewards' => 'required|json',
        ]);

        SpecialDeal::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'price' => $request->price,
            'rewards' => json_decode($request->rewards, true),
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Special Deal Created!');
    }

    public function deleteSpecialDeal($id)
    {
        SpecialDeal::destroy($id);
        return redirect()->back()->with('success', 'Special Deal Deleted!');
    }

    // --- GIVEAWAYS ---
    public function giveaways()
    {
        $giveaways = Giveaway::orderBy('id', 'desc')->paginate(10);
        return view('panel.giveaways', compact('giveaways'));
    }

    public function addGiveaway(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'prizes' => 'required|json',
        ]);

        Giveaway::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'prizes' => json_decode($request->prizes, true),
            'requirements' => $request->requirements ? json_decode($request->requirements, true) : null,
            'processed' => false
        ]);

        return redirect()->back()->with('success', 'Giveaway Created!');
    }

    public function deleteGiveaway($id)
    {
        Giveaway::destroy($id);
        return redirect()->back()->with('success', 'Giveaway Deleted!');
    }

    // --- DAILY REWARDS (ATTENDANCE) ---
    public function dailyRewards()
    {
        $rewards = AttendanceReward::orderBy('price', 'asc')->get();
        return view('panel.daily_rewards', compact('rewards'));
    }

    public function updateDailyReward(Request $request)
    {
        $request->validate([
            'price' => 'required|integer', // This is the DAY NUMBER
            'item' => 'required|string'
        ]);

        AttendanceReward::updateOrCreate(
            ['price' => $request->price],
            ['item' => $request->item]
        );

        return redirect()->back()->with('success', 'Daily Reward Updated!');
    }

    public function deleteDailyReward($id)
    {
        AttendanceReward::destroy($id);
        return redirect()->back()->with('success', 'Reward Deleted!');
    }
}