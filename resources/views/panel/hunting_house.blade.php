@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Hunting House</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage material exchanges and crafting recipes.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#F97316] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2 uppercase">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Add Exchange
    </button>
</div>

<!-- List -->
<div class="bg-white border-2 border-black shadow-neo">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-sm font-black uppercase text-black bg-[#FED7AA] border-b-2 border-black">
                <tr>
                    <th class="px-6 py-4 border-r-2 border-black">Target Item</th>
                    <th class="px-6 py-4 border-r-2 border-black">Category</th>
                    <th class="px-6 py-4 border-r-2 border-black">Requirements</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-black">
                @php
                    $itemMap = collect($allItems)->pluck('name', 'id')->toArray();
                    $skillMap = collect($allSkills)->pluck('name', 'id')->toArray(); // Just in case checks
                    
                    // Merger helper for display
                    $getName = function($id) use ($itemMap, $skillMap) {
                        return $itemMap[$id] ?? $skillMap[$id] ?? $id;
                    };
                @endphp
                @foreach($items as $item)
                @php
                    $reqString = "";
                    if(is_array($item->materials)) {
                        $parts = [];
                        foreach($item->materials as $idx => $mat) {
                            $qty = $item->quantities[$idx] ?? 1;
                            $parts[] = "$mat:$qty";
                        }
                        $reqString = implode(', ', $parts);
                    }
                @endphp
                <tr class="hover:bg-[#FFF7ED] transition-colors font-medium">
                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="font-bold text-gray-900">{{ $getName($item->item_id) }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ $item->item_id }}</div>
                    </td>
                    <td class="px-6 py-4 border-r-2 border-black uppercase text-xs font-bold tracking-wider">
                        {{ $item->category }}
                    </td>
                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="flex flex-wrap gap-2">
                            @if(is_array($item->materials))
                                @foreach($item->materials as $index => $matId)
                                    <span class="inline-flex items-center px-2 py-1 border border-black bg-white text-xs font-mono font-bold shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" title="{{ $getName($matId) }}">
                                        {{ $matId }} (x{{ $item->quantities[$index] ?? 1 }})
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                         <div class="flex justify-end gap-2">
                            <button onclick="openEditModal('{{ $item->id }}', '{{ $item->item_id }}', '{{ $reqString }}', '{{ $item->category }}')" 
                                class="text-blue-600 font-bold hover:underline uppercase text-xs">Edit</button>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('panel.hunting_house.delete', $item->id) }}" method="POST" onsubmit="return confirm('Delete this exchange?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 font-bold hover:underline uppercase text-xs">Delete</button>
                            </form>
                         </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t-2 border-black bg-gray-50">
        {{ $items->links() }}
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left transform transition-all sm:max-w-xl w-full p-0">
             <form action="{{ route('panel.hunting_house.add') }}" method="POST" onsubmit="prepareSubmission('add')">
                @csrf
                <div class="bg-[#F97316] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-black italic tracking-tighter uppercase">NEW EXCHANGE</h3>
                    <button type="button" onclick="closeAddModal()" class="text-black hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-8 bg-white space-y-6">
                    <!-- Target Selector -->
                    <div class="relative">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Target Item ID</label>
                        <input type="text" id="add_target_search" placeholder="Search Item or Skill..." 
                             class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(249,115,22,1)] transition-all" autocomplete="off">
                        <input type="hidden" name="item_id" id="add_target_id" required>
                        <ul id="add_target_list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                    </div>

                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Category</label>
                        <select name="category" id="add_category" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(249,115,22,1)] transition-all uppercase">
                            <option value="material">Material</option>
                            <option value="weapon">Weapon</option>
                            <option value="clothing">Clothing</option> <!-- Usually 'set' -->
                            <option value="back_item">Back Item</option>
                            <option value="accessory">Accessory</option> <!-- Added -->
                            <option value="skill">Skill</option> <!-- Added just in case -->
                        </select>
                    </div>

                    <!-- Requirements Builder -->
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Requirements</label>
                         <!-- List of added reqs -->
                        <div id="add_req_list_container" class="mb-4 space-y-2">
                             <p id="add_req_empty_msg" class="text-gray-400 text-xs italic">No requirements added yet.</p>
                        </div>
                        <input type="hidden" name="requirements" id="add_requirements_final">

                         <!-- Add New Req -->
                         <div class="flex gap-2 items-end p-4 bg-gray-50 border-2 border-black border-dashed">
                             <div class="flex-1 relative">
                                <label class="text-[10px] font-bold uppercase text-gray-500 mb-1 block">Item/Skill</label>
                                <input type="text" id="add_req_search" placeholder="Search..." 
                                    class="block w-full text-xs font-bold border-2 border-black px-2 py-2" autocomplete="off">
                                <input type="hidden" id="add_req_temp_id">
                                <ul id="add_req_search_list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-lg max-h-40 overflow-y-auto hidden"></ul>
                             </div>
                             <div class="w-20">
                                <label class="text-[10px] font-bold uppercase text-gray-500 mb-1 block">Qty</label>
                                <input type="number" id="add_req_qty" value="1" min="1" class="block w-full text-xs font-bold border-2 border-black px-2 py-2">
                             </div>
                             <button type="button" onclick="addRequirement('add')" class="bg-black text-white px-3 py-2 text-xs font-bold border-2 border-black uppercase hover:bg-gray-800 tracking-wider">
                                 + Add
                             </button>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#F97316] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">Create</button>
                    <button type="button" onclick="closeAddModal()" class="w-full sm:w-auto bg-white text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">Cancel</button>
                </div>
             </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left transform transition-all sm:max-w-xl w-full p-0">
             <form action="{{ route('panel.hunting_house.update') }}" method="POST" onsubmit="prepareSubmission('edit')">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                
                <div class="bg-black px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">UPDATE EXCHANGE</h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-300 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-8 bg-white space-y-6">
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-400 mb-2 pl-2">Target Item ID (Locked)</label>
                        <input type="text" id="edit_item_id_display" class="block w-full text-sm font-bold border-2 border-gray-300 bg-gray-100 px-4 py-3 cursor-not-allowed" readonly>
                    </div>
                     <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Category</label>
                        <select name="category" id="edit_category" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all uppercase">
                            <option value="material">Material</option>
                            <option value="weapon">Weapon</option>
                            <option value="clothing">Clothing</option>
                            <option value="back_item">Back Item</option>
                            <option value="accessory">Accessory</option>
                            <option value="skill">Skill</option>
                        </select>
                    </div>

                    <!-- Requirements Builder (Edit) -->
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Requirements</label>
                        
                        <!-- List of added reqs -->
                        <div id="edit_req_list_container" class="mb-4 space-y-2"></div>
                        <input type="hidden" name="requirements" id="edit_requirements_final">

                        <!-- Add New Req -->
                         <div class="flex gap-2 items-end p-4 bg-gray-50 border-2 border-black border-dashed">
                             <div class="flex-1 relative">
                                <label class="text-[10px] font-bold uppercase text-gray-500 mb-1 block">Item/Skill</label>
                                <input type="text" id="edit_req_search" placeholder="Search..." 
                                    class="block w-full text-xs font-bold border-2 border-black px-2 py-2" autocomplete="off">
                                <input type="hidden" id="edit_req_temp_id">
                                <ul id="edit_req_search_list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-lg max-h-40 overflow-y-auto hidden"></ul>
                             </div>
                             <div class="w-20">
                                <label class="text-[10px] font-bold uppercase text-gray-500 mb-1 block">Qty</label>
                                <input type="number" id="edit_req_qty" value="1" min="1" class="block w-full text-xs font-bold border-2 border-black px-2 py-2">
                             </div>
                             <button type="button" onclick="addRequirement('edit')" class="bg-black text-white px-3 py-2 text-xs font-bold border-2 border-black uppercase hover:bg-gray-800 tracking-wider">
                                 + Add
                             </button>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase hover:bg-gray-900">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" class="w-full sm:w-auto bg-white text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">Cancel</button>
                </div>
             </form>
        </div>
    </div>
</div>

<script>
    // DATA
    const allItems = @json($allItems);
    const allSkills = @json($allSkills);
    
    // Combine for global search
    const combinedData = [
        ...allItems.map(i => ({...i, _type: 'item', search_label: `${i.name} (Item)`})),
        ...allSkills.map(s => ({...s, _type: 'skill', search_label: `${s.name} (Skill)`}))
    ];

    // State
    let requirementsMap = {
        'add': [],
        'edit': []
    };

    function setupSearch(inputId, listId, data, onSelect) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        
        if(!input || !list) return;

        function render(results) {
            list.innerHTML = '';
            if (results.length === 0) {
                list.innerHTML = '<li class="px-4 py-2 text-xs font-bold text-gray-500 border-b border-gray-200">NO RESULTS</li>';
                return;
            }
            results.slice(0, 15).forEach(obj => { // Limit 15
                const li = document.createElement('li');
                li.className = 'px-4 py-2 text-xs font-bold text-gray-900 hover:bg-gray-100 cursor-pointer border-b border-gray-100';
                li.textContent = `${obj.search_label} [${obj.id}]`;
                li.addEventListener('click', () => {
                    onSelect(obj);
                    list.classList.add('hidden');
                });
                list.appendChild(li);
            });
        }

        input.addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase();
            if (val.length < 1) {
                list.classList.add('hidden');
                return;
            }
            const filtered = data.filter(obj => 
                (obj.name && obj.name.toLowerCase().includes(val)) || String(obj.id).includes(val)
            );
            render(filtered);
            list.classList.remove('hidden');
        });

        // Hide on outside click
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                list.classList.add('hidden');
            }
        });
    }

    // Initialize Searchers
    document.addEventListener('DOMContentLoaded', () => {
        // Add Modal -> Target Selection
        setupSearch('add_target_search', 'add_target_list', combinedData, (obj) => {
            document.getElementById('add_target_search').value = obj.name; 
            document.getElementById('add_target_id').value = obj.id;
            
            // Auto-detect type
            const catSelect = document.getElementById('add_category');
            if (obj._type === 'skill') {
                catSelect.value = 'skill';
            } else if (obj.type) {
                // Determine category based on type
                if(obj.type === 'item' || obj.type === 'magatama') {
                    catSelect.value = 'material';
                } else if(obj.type === 'wpn') {
                    catSelect.value = 'weapon';
                } else if(obj.type === 'set') {
                    catSelect.value = 'clothing';
                } else if(obj.type === 'back') {
                    catSelect.value = 'back_item';
                } else if(obj.type === 'accessory') {
                    catSelect.value = 'accessory';
                } else {
                    catSelect.value = 'material'; // Default fallback
                }
            } else {
                catSelect.value = 'material';
            }
        });

        // Add Modal -> Requirement Search
        setupSearch('add_req_search', 'add_req_search_list', combinedData, (obj) => {
            document.getElementById('add_req_search').value = obj.name; 
            document.getElementById('add_req_temp_id').value = obj.id;
        });

        // Edit Modal -> Requirement Search
        setupSearch('edit_req_search', 'edit_req_search_list', combinedData, (obj) => {
            document.getElementById('edit_req_search').value = obj.name; 
            document.getElementById('edit_req_temp_id').value = obj.id;
        });
    });

    // Requirements Logic
    function addRequirement(mode) {
        let finalId = document.getElementById(`${mode}_req_temp_id`).value;
        const nameVal = document.getElementById(`${mode}_req_search`).value;
        if(!finalId) finalId = nameVal; // Fallback

        if(!finalId) return alert("Please select an item or enter an ID.");

        const qty = document.getElementById(`${mode}_req_qty`).value;
        
        // Add to array
        requirementsMap[mode].push({ id: finalId, qty: qty, name: nameVal });
        
        // Reset Inputs
        document.getElementById(`${mode}_req_search`).value = '';
        document.getElementById(`${mode}_req_temp_id`).value = '';
        document.getElementById(`${mode}_req_qty`).value = 1;

        renderRequirements(mode);
    }

    function removeRequirement(mode, index) {
        requirementsMap[mode].splice(index, 1);
        renderRequirements(mode);
    }

    function renderRequirements(mode) {
        const container = document.getElementById(`${mode}_req_list_container`);
        if(!container) return;
        
        container.innerHTML = '';
        
        if (requirementsMap[mode].length === 0) {
             container.innerHTML = '<p class="text-gray-400 text-xs italic">No requirements added yet.</p>';
             return;
        }

        requirementsMap[mode].forEach((req, idx) => {
            let name = req.name;
            if(!name || name === req.id) {
                const found = combinedData.find(d => String(d.id) === String(req.id));
                if(found) name = found.name;
                else name = req.id;
            }

            const div = document.createElement('div');
            div.className = 'flex justify-between items-center bg-white border border-black p-2 shadow-sm';
            div.innerHTML = `
                <div class="text-xs font-bold">
                    <span class="text-gray-400 mr-2">#${req.id}</span>
                    <span class="uppercase">${name}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-mono font-bold bg-gray-100 px-2">x${req.qty}</span>
                    <button type="button" onclick="removeRequirement('${mode}', ${idx})" class="text-red-500 font-bold hover:underline text-[10px] uppercase">[DEL]</button>
                </div>
            `;
            container.appendChild(div);
        });
    }

    function prepareSubmission(mode) {
        const finalStr = requirementsMap[mode].map(r => `${r.id}:${r.qty}`).join(', ');
        document.getElementById(`${mode}_requirements_final`).value = finalStr;
        return true;
    }

    // Modal Controls
    function openAddModal() { 
        document.getElementById('addModal').classList.remove('hidden'); 
        requirementsMap['add'] = [];
        renderRequirements('add');
    }
    
    function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }

    function openEditModal(id, itemId, reqString, category) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_item_id_display').value = itemId;

        const catSelect = document.getElementById('edit_category');
        if(catSelect) catSelect.value = category;

        requirementsMap['edit'] = [];
        if(reqString) {
            const parts = reqString.split(',');
            parts.forEach(p => {
                const [pid, pqty] = p.split(':');
                if(pid) {
                    requirementsMap['edit'].push({
                        id: pid.trim(),
                        qty: pqty ? pqty.trim() : 1,
                        name: pid.trim() 
                    });
                }
            });
        }
        renderRequirements('edit');
        
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeAddModal();
            closeEditModal();
        }
    });
</script>
@endsection
