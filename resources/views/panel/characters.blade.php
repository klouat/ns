@extends('panel.layout')

@section('content')

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Character Database</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage players, tokens, and account status.</p>
    </div>
    
    <form method="GET" action="{{ route('panel.characters') }}" class="w-full md:w-auto relative">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" 
                class="pl-10 pr-4 py-3 border-2 border-black font-bold text-sm w-full md:w-80 shadow-neo focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,144,232,1)] transition-all placeholder:text-gray-400 placeholder:font-normal" 
                placeholder="SEARCH ID, NAME...">
        </div>
    </form>
</div>

<!-- Characters List -->
<div class="bg-white border-2 border-black shadow-neo">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-sm font-black uppercase text-black bg-[#FF90E8] border-b-2 border-black">
                <tr>
                    <th class="px-6 py-4 border-r-2 border-black">ID</th>
                    <th class="px-6 py-4 border-r-2 border-black">Character</th>
                    <th class="px-6 py-4 border-r-2 border-black">User Status</th> 
                    <th class="px-6 py-4 border-r-2 border-black">Rank / Level</th>
                    <th class="px-6 py-4 border-r-2 border-black">Assets</th> 
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-black">
                @foreach($characters as $char)
                <tr class="hover:bg-[#FFF0F5] transition-colors font-medium">
                    <td class="px-6 py-4 border-r-2 border-black font-mono font-bold text-gray-900">#{{ $char->id }}</td>
                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="font-black text-gray-900 uppercase tracking-tight text-lg">{{ $char->name }}</div>
                        <div class="text-xs font-bold text-gray-500 font-mono mt-1">UID: {{ $char->user_id }}</div>
                    </td>
                    
                    <td class="px-6 py-4 border-r-2 border-black">
                        @if($char->user->account_type == 1)
                            <span class="inline-flex items-center px-3 py-1 border-2 border-black bg-[#FFDA55] text-black text-xs font-black uppercase tracking-wide shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                                Premium
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 border-2 border-black bg-gray-100 text-gray-600 text-xs font-black uppercase tracking-wide">
                                Free
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="text-gray-900 font-black text-lg">LVL {{ $char->level }}</div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $char->rank }}</div>
                    </td>

                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="text-[#D97706] font-bold text-xs font-mono">GOLD: {{ number_format($char->gold) }}</div>
                        <div class="text-[#4F46E5] font-bold text-xs font-mono">TOKS: {{ number_format($char->user->tokens ?? 0) }}</div>
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
                            
                            data-tokens="{{ $char->user->tokens ?? 0 }}"
                            data-type="{{ $char->user->account_type ?? 0 }}"
                            
                            data-el1="{{ $char->element_1 }}"
                            data-el2="{{ $char->element_2 }}"
                            data-el3="{{ $char->element_3 }}"
                            data-p-fire="{{ $char->point_fire }}"
                            data-p-water="{{ $char->point_water }}"
                            data-p-wind="{{ $char->point_wind }}"
                            data-p-earth="{{ $char->point_earth }}"
                            data-p-light="{{ $char->point_lightning }}"
                            class="bg-white text-black px-4 py-2 text-xs font-black border-2 border-black shadow-neo-sm hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                            Open File
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t-2 border-black bg-gray-50">
        {{ $characters->appends(request()->query())->links() }}
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-2xl w-full p-0">
            
            <form action="{{ route('panel.characters.update') }}" method="POST">
                @csrf
                <input type="hidden" name="character_id" id="modal_id">

                <div class="bg-[#FF90E8] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-black italic tracking-tighter uppercase">EDIT CHARACTER</h3>
                    <button type="button" onclick="closeEditModal()" class="text-black hover:bg-black hover:text-white border-2 border-transparent hover:border-black p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 max-h-[75vh] overflow-y-auto neo-scroll">
                    
                    <!-- Account Section -->
                    <div class="bg-[#FFFBEB] border-2 border-black p-6 mb-8 shadow-neo-sm">
                        <h4 class="text-sm font-black text-[#B45309] mb-4 uppercase tracking-widest border-b-2 border-[#FCD34D] pb-1">Master Account Settings</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase text-gray-900 mb-2">User Tokens</label>
                                <input type="number" name="tokens" id="modal_tokens" min="0" required 
                                    class="block w-full text-sm font-bold border-2 border-black px-3 py-2 bg-white focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,144,232,1)] transition-all">
                                <p class="text-[10px] font-bold text-gray-500 mt-1 uppercase">Warning: Affects all chars</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase text-gray-900 mb-2">Account Type</label>
                                <select name="account_type" id="modal_type" 
                                    class="block w-full text-sm font-bold border-2 border-black px-3 py-2 bg-white focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,144,232,1)] transition-all">
                                    <option value="0">Free User</option>
                                    <option value="1">Emblem User (Premium)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Char Details -->
                    <h4 class="text-sm font-black text-gray-900 mb-4 uppercase tracking-widest border-b-2 border-black pb-1">Character Data</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Name</label>
                            <input type="text" name="name" id="modal_name" required class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Rank</label>
                            <select name="rank" id="modal_rank" class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                                <option value="Genin">Genin</option>
                                <option value="Chunin">Chunin</option>
                                <option value="Jounin">Jounin</option>
                                <option value="Senior Ninja Tutor">Senior Ninja Tutor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Level</label>
                            <input type="number" name="level" id="modal_level" min="1" required class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Gold</label>
                            <input type="number" name="gold" id="modal_gold" min="0" required class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">XP</label>
                            <input type="number" name="xp" id="modal_xp" min="0" class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Talent Points (TP)</label>
                            <input type="number" name="tp" id="modal_tp" min="0" class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Senjutsu (SS)</label>
                            <input type="number" name="character_ss" id="modal_ss" min="0" class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-900 mb-1">Gender</label>
                            <select name="gender" id="modal_gender" class="block w-full text-sm font-bold border-2 border-black px-3 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
                                <option value="0">Male</option>
                                <option value="1">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="h-1 bg-black my-6"></div>

                    <h4 class="text-xs font-black text-gray-500 mb-4 uppercase">Elements</h4>
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <div><input type="number" name="element_1" id="modal_el1" placeholder="El 1" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all text-center"></div>
                        <div><input type="number" name="element_2" id="modal_el2" placeholder="El 2" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all text-center"></div>
                        <div><input type="number" name="element_3" id="modal_el3" placeholder="El 3" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all text-center"></div>
                    </div>

                    <h4 class="text-xs font-black text-gray-500 mb-4 uppercase">Attribute Points</h4>
                    <div class="grid grid-cols-5 gap-2">
                        <input type="number" name="point_fire" id="modal_p_fire" placeholder="Fire" class="block w-full text-sm font-bold border-2 border-red-500 bg-red-50 text-red-900 px-1 py-2 text-center focus:ring-0">
                        <input type="number" name="point_water" id="modal_p_water" placeholder="Water" class="block w-full text-sm font-bold border-2 border-blue-500 bg-blue-50 text-blue-900 px-1 py-2 text-center focus:ring-0">
                        <input type="number" name="point_wind" id="modal_p_wind" placeholder="Wind" class="block w-full text-sm font-bold border-2 border-green-500 bg-green-50 text-green-900 px-1 py-2 text-center focus:ring-0">
                        <input type="number" name="point_earth" id="modal_p_earth" placeholder="Earth" class="block w-full text-sm font-bold border-2 border-yellow-600 bg-yellow-50 text-yellow-900 px-1 py-2 text-center focus:ring-0">
                        <input type="number" name="point_lightning" id="modal_p_light" placeholder="Light" class="block w-full text-sm font-bold border-2 border-purple-500 bg-purple-50 text-purple-900 px-1 py-2 text-center focus:ring-0">
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#FF90E8] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" class="w-full sm:w-auto bg-white text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Cancel
                    </button>
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