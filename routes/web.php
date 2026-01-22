<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AmfController;
use App\Http\Controllers\AdminPanelController;

Route::get('/', function () {
    return view('welcome');
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