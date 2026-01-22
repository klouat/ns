@extends('panel.layout')

@section('content')

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Character Database</h1>
        <p class="text-sm text-gray-500">Manage players, tokens, and account status.</p>
    </div>
    
    <form method="GET" action="{{ route('panel.characters') }}" class="w-full md:w-auto relative">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" 
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm w-full md:w-64" 
                placeholder="Search ID, Name or User ID...">
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden relative">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Character</th>
                    <th class="px-6 py-3">User Status</th> <th class="px-6 py-3">Rank / Level</th>
                    <th class="px-6 py-3">Gold / Tokens</th> <th class="px-6 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($characters as $char)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">#{{ $char->id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $char->name }}</div>
                        <div class="text-xs text-gray-400">User ID: {{ $char->user_id }}</div>
                    </td>
                    
                    <td class="px-6 py-4">
                        @if($char->user->account_type == 1)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5"></span> Emblem
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                Free User
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-gray-900 font-semibold">Lvl {{ $char->level }}</div>
                        <div class="text-xs text-gray-500">{{ $char->rank }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-amber-600 font-medium text-xs">Gold: {{ number_format($char->gold) }}</div>
                        <div class="text-indigo-600 font-medium text-xs">Tokens: {{ number_format($char->user->tokens ?? 0) }}</div>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <button type="button" 
                            onclick="openEditModal(this)"
                            data-id="{{ $char->id }}"
                            data-name="{{ $char->name }}"
                            data-rank="{{ $char->rank }}"
                            data-level="{{ $char->level }}"
                            data-gold="{{ $char->gold }}"
                            data-xp="{{ $char->xp }}"
                            data-tp="{{ $char->tp }}"
                            data-ss="{{ $char->character_ss }}"
                            data-gender="{{ $char->gender }}"
                            
                            /* NEW DATA ATTRIBUTES FOR USER */
                            data-tokens="{{ $char->user->tokens ?? 0 }}"
                            data-type="{{ $char->user->account_type ?? 0 }}"
                            
                            /* Elements & Points */
                            data-el1="{{ $char->element_1 }}"
                            data-el2="{{ $char->element_2 }}"
                            data-el3="{{ $char->element_3 }}"
                            data-p-fire="{{ $char->point_fire }}"
                            data-p-water="{{ $char->point_water }}"
                            data-p-wind="{{ $char->point_wind }}"
                            data-p-earth="{{ $char->point_earth }}"
                            data-p-light="{{ $char->point_lightning }}"
                            class="text-indigo-600 hover:text-indigo-900 font-medium text-sm border border-indigo-200 rounded px-3 py-1 hover:bg-indigo-50 transition-colors">
                            Edit
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        {{ $characters->appends(request()->query())->links() }}
    </div>
</div>

<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeEditModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
        <div class="relative bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl w-full">
            
            <form action="{{ route('panel.characters.update') }}" method="POST">
                @csrf
                <input type="hidden" name="character_id" id="modal_id">

                <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-white">Edit User & Character</h3>
                    <button type="button" onclick="closeEditModal()" class="text-indigo-200 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-6 max-h-[75vh] overflow-y-auto">
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-bold text-yellow-800 mb-3 uppercase tracking-wide">User Account Settings</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">User Tokens</label>
                                <input type="number" name="tokens" id="modal_tokens" min="0" required 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                <p class="text-xs text-gray-500 mt-1">Shared by all chars on this account.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                                <select name="account_type" id="modal_type" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    <option value="0">Free User</option>
                                    <option value="1">Emblem User (Premium)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide">Character Stats</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" name="name" id="modal_name" required class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rank</label>
                            <select name="rank" id="modal_rank" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                                <option value="Genin">Genin</option>
                                <option value="Chunin">Chunin</option>
                                <option value="Jounin">Jounin</option>
                                <option value="Senior Ninja Tutor">Senior Ninja Tutor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                            <input type="number" name="level" id="modal_level" min="1" required class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gold</label>
                            <input type="number" name="gold" id="modal_gold" min="0" required class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">XP</label>
                            <input type="number" name="xp" id="modal_xp" min="0" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Talent Points (TP)</label>
                            <input type="number" name="tp" id="modal_tp" min="0" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senjutsu Scroll (SS)</label>
                            <input type="number" name="character_ss" id="modal_ss" min="0" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="gender" id="modal_gender" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm border p-2">
                                <option value="0">Male</option>
                                <option value="1">Female</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4 border-gray-200">

                    <h4 class="text-xs font-bold text-gray-500 mb-2 uppercase">Elements</h4>
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <div><input type="number" name="element_1" id="modal_el1" placeholder="El 1" class="block w-full border-gray-300 rounded-md shadow-sm text-sm border p-1"></div>
                        <div><input type="number" name="element_2" id="modal_el2" placeholder="El 2" class="block w-full border-gray-300 rounded-md shadow-sm text-sm border p-1"></div>
                        <div><input type="number" name="element_3" id="modal_el3" placeholder="El 3" class="block w-full border-gray-300 rounded-md shadow-sm text-sm border p-1"></div>
                    </div>

                    <h4 class="text-xs font-bold text-gray-500 mb-2 uppercase">Attribute Points</h4>
                    <div class="grid grid-cols-5 gap-2">
                        <input type="number" name="point_fire" id="modal_p_fire" placeholder="Fire" class="block w-full border-red-200 rounded-md text-sm border p-1">
                        <input type="number" name="point_water" id="modal_p_water" placeholder="Water" class="block w-full border-blue-200 rounded-md text-sm border p-1">
                        <input type="number" name="point_wind" id="modal_p_wind" placeholder="Wind" class="block w-full border-green-200 rounded-md text-sm border p-1">
                        <input type="number" name="point_earth" id="modal_p_earth" placeholder="Earth" class="block w-full border-yellow-200 rounded-md text-sm border p-1">
                        <input type="number" name="point_lightning" id="modal_p_light" placeholder="Light" class="block w-full border-purple-200 rounded-md text-sm border p-1">
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse rounded-b-xl">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');

    function openEditModal(button) {
        const d = button.dataset;

        // IDs
        document.getElementById('modal_id').value = d.id;
        
        // Account Data (NEW)
        document.getElementById('modal_tokens').value = d.tokens;
        document.getElementById('modal_type').value = d.type;

        // Character Data
        document.getElementById('modal_name').value = d.name;
        document.getElementById('modal_rank').value = d.rank;
        document.getElementById('modal_level').value = d.level;
        document.getElementById('modal_gold').value = d.gold;
        document.getElementById('modal_xp').value = d.xp;
        document.getElementById('modal_tp').value = d.tp;
        document.getElementById('modal_ss').value = d.ss;
        document.getElementById('modal_gender').value = d.gender;

        // Elements & Points
        document.getElementById('modal_el1').value = d.el1;
        document.getElementById('modal_el2').value = d.el2;
        document.getElementById('modal_el3').value = d.el3;
        document.getElementById('modal_p_fire').value = d.pFire;
        document.getElementById('modal_p_water').value = d.pWater;
        document.getElementById('modal_p_wind').value = d.pWind;
        document.getElementById('modal_p_earth').value = d.pEarth;
        document.getElementById('modal_p_light').value = d.pLight;

        modal.classList.remove('hidden');
    }

    function closeEditModal() {
        modal.classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") closeEditModal();
    });
</script>

@endsection