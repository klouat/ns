@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Limited Store</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage Mysterious Market skill pairs.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#8B5CF6] text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        ADD NEW PAIR
    </button>
</div>

<div class="grid grid-cols-1 gap-8">

    <!-- LIST -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#FFDA55]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Active Store Groups</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-sm font-black uppercase text-black bg-gray-100 border-b-2 border-black">
                    <tr>
                        <th class="px-6 py-4 border-r-2 border-black">Group ID</th>
                        <th class="px-6 py-4 border-r-2 border-black">Items</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    @php
                        $skillMap = collect($allSkills)->pluck('name', 'id')->toArray();
                    @endphp
                    @foreach($items->groupBy('group_id') as $groupId => $groupItems)
                    @php
                        $baseItem = $groupItems->where('sort_order', 1)->first();
                        $upgradeItem = $groupItems->where('sort_order', 2)->first();
                    @endphp
                    <tr class="hover:bg-[#F0F0FF] transition-colors font-medium">
                        <td class="px-6 py-4 border-r-2 border-black font-mono text-sm text-gray-700">{{ $groupId }}</td>
                        <td class="px-6 py-4 border-r-2 border-black">
                            @foreach($groupItems->sortBy('sort_order') as $item)
                                <div class="flex items-start gap-4 mb-4 last:mb-0">
                                    <span class="w-24 text-center py-1 text-xs font-black uppercase border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] {{ $item->sort_order == 1 ? 'bg-[#A5F3FC]' : 'bg-[#BBF7D0]' }}">
                                        {{ $item->sort_order == 1 ? 'Base' : 'Upgrade' }}
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 leading-tight">{{ $skillMap[$item->item_id] ?? 'Unknown Skill' }}</span>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-xs font-bold font-mono bg-gray-200 border border-black px-2 py-0.5">{{ $item->item_id }}</span>
                                            <span class="text-xs font-bold text-gray-500">({{ $item->price_token }} Tokens)</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-right">
                             <div class="flex justify-end gap-3">
                                <button onclick="openEditModal('{{ $groupId }}', '{{ $baseItem->item_id ?? '' }}', '{{ $baseItem->price_token ?? '' }}', '{{ $upgradeItem->item_id ?? '' }}', '{{ $upgradeItem->price_token ?? '' }}')" 
                                    class="bg-white text-black px-3 py-1 text-xs font-bold border-2 border-black shadow-neo-sm hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                                    Edit
                                </button>
                                
                                <form action="{{ route('panel.limited_store.delete', $groupId) }}" method="POST" onsubmit="return confirm('Delete this group?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-[#FF6B6B] text-black px-3 py-1 text-xs font-bold border-2 border-black shadow-neo-sm hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                                        Delete
                                    </button>
                                </form>
                             </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-2xl w-full p-0">
            
            <form action="{{ route('panel.limited_store.add') }}" method="POST">
                @csrf
                <div class="bg-[#8B5CF6] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">NEW PAIRING</h3>
                    <button type="button" onclick="closeAddModal()" class="text-white hover:bg-black hover:text-white border-2 border-transparent hover:border-white p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 bg-white">
                    <div class="mb-6">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#8B5CF6] pl-2">Group Identifier</label>
                        <input type="text" name="group_name" placeholder="E.G. FIRE KINJUTSU" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(139,92,246,1)] transition-all placeholder:font-normal placeholder:text-gray-400" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Base Skill Search -->
                        <div class="relative">
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#A5F3FC] pl-2">Base Skill</label>
                            <input type="text" id="base-search-input" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(165,243,252,1)] transition-all placeholder:font-normal placeholder:text-gray-400" placeholder="SEARCH SKILL..." autocomplete="off">
                            <input type="hidden" name="base_skill_id" id="base-real-id" required>
                            <ul id="base-results-list" class="absolute z-10 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                        </div>

                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#A5F3FC] pl-2">Base Price</label>
                            <input type="number" name="base_price" value="200" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(165,243,252,1)] transition-all" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <!-- Upgrade Skill Search -->
                        <div class="relative">
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#BBF7D0] pl-2">Upgrade Skill</label>
                            <input type="text" id="upgrade-search-input" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(187,247,208,1)] transition-all placeholder:font-normal placeholder:text-gray-400" placeholder="SEARCH SKILL..." autocomplete="off">
                            <input type="hidden" name="upgrade_skill_id" id="upgrade-real-id" required>
                            <ul id="upgrade-results-list" class="absolute z-10 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                        </div>

                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#BBF7D0] pl-2">Upgrade Price</label>
                            <input type="number" name="upgrade_price" value="300" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(187,247,208,1)] transition-all" required>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#8B5CF6] text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Confirm Create
                    </button>
                    <button type="button" onclick="closeAddModal()" class="w-full sm:w-auto bg-white text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-2xl w-full p-0">
            
            <form action="{{ route('panel.limited_store.update') }}" method="POST">
                @csrf
                <input type="hidden" name="group_id" id="edit_group_id">

                <div class="bg-black px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">UPDATE PRICES</h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:bg-white hover:text-black border-2 border-transparent hover:border-black p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-400 mb-2 pl-2">Base Skill ID <span class="text-[10px] ml-1">(LOCKED)</span></label>
                            <input type="text" id="edit_base_id" class="block w-full text-sm font-mono border-2 border-gray-300 px-4 py-3 bg-gray-100 cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#A5F3FC] pl-2">Base Price</label>
                            <input type="number" name="base_price" id="edit_base_price" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-400 mb-2 pl-2">Upgrade Skill ID <span class="text-[10px] ml-1">(LOCKED)</span></label>
                            <input type="text" id="edit_upgrade_id" class="block w-full text-sm font-mono border-2 border-gray-300 px-4 py-3 bg-gray-100 cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#BBF7D0] pl-2">Upgrade Price</label>
                            <input type="number" name="upgrade_price" id="edit_upgrade_price" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#10B981] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
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
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(groupId, baseId, basePrice, upgradeId, upgradePrice) {
        document.getElementById('edit_group_id').value = groupId;
        document.getElementById('edit_base_id').value = baseId;
        document.getElementById('edit_base_price').value = basePrice;
        document.getElementById('edit_upgrade_id').value = upgradeId;
        document.getElementById('edit_upgrade_price').value = upgradePrice;
        
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeAddModal();
            closeEditModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const allSkills = @json($allSkills);

        function setupSearch(inputId, listId, hiddenId, data) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);

            if (!input || !list || !hidden) return;

            function render(results) {
                list.innerHTML = '';
                if (results.length === 0) {
                    list.innerHTML = '<li class="px-4 py-3 text-sm font-bold text-gray-500 border-b-2 border-black">NO RESULTS FOUND</li>';
                    return;
                }

                results.forEach(obj => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-3 text-sm font-bold text-gray-900 hover:bg-[#A5F3FC] cursor-pointer transition-colors border-b-2 border-black last:border-0';
                    li.textContent = `${obj.name} (ID: ${obj.id})`;

                    li.addEventListener('click', () => {
                        input.value = obj.name;
                        hidden.value = obj.id;
                        list.classList.add('hidden');
                    });

                    list.appendChild(li);
                });
            }

            input.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase();
                if (val.length < 1) {
                    list.classList.add('hidden');
                    return;
                }

                const filtered = data.filter(obj => 
                    (obj.name && obj.name.toLowerCase().includes(val)) || String(obj.id).includes(val)
                );

                render(filtered.slice(0, 10));
                list.classList.remove('hidden');
            });

            input.addEventListener('focus', function() {
                if (input.value === '') {
                    render(data.slice(0, 10));
                    list.classList.remove('hidden');
                }
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.add('hidden');
                }
            });
        }

        setupSearch('base-search-input', 'base-results-list', 'base-real-id', allSkills);
        setupSearch('upgrade-search-input', 'upgrade-results-list', 'upgrade-real-id', allSkills);
    });
</script>
@endsection
