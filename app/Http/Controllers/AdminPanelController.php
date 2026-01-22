<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\CharacterItem;
use App\Models\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

        $allowedTypes = ['wpn', 'set', 'hair', 'back', 'accessory'];

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
            'sender_name' => 'Admin',
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
}