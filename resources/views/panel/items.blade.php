@extends('panel.layout')

@section('content')

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Item Library</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage character inventory and bulk actions.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">

    <!-- ADD SINGLE ITEM -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#A5F3FC]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Add Single Item</h5>
        </div>
        
        <div class="p-6">
            <form action="{{ route('panel.items.add') }}" method="POST">
                @csrf
                
                <div class="mb-5 relative" id="char-search-component">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Select Character</label>
                    <input 
                        type="text" 
                        id="char-search-input" 
                        class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(165,243,252,1)] transition-all placeholder:font-normal placeholder:text-gray-400"
                        placeholder="Start typing name..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="character_id" id="real-char-id" required>
                    <ul id="char-results-list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                </div>

                <div class="mb-6 relative" id="item-search-component">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Select Item</label>
                    <input 
                        type="text" 
                        id="item-search-input" 
                        class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(165,243,252,1)] transition-all placeholder:font-normal placeholder:text-gray-400"
                        placeholder="Search item name or ID..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="item_id" id="real-item-id" required>
                    <ul id="item-results-list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                </div>

                <button type="submit" 
                    class="w-full bg-[#A5F3FC] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                    Add Item
                </button>
            </form>
        </div>
    </div>

    <!-- BULK ACTIONS -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-black">
            <h5 class="text-lg font-black uppercase tracking-widest text-white">Bulk Actions</h5>
        </div>
        
        <div class="p-6">
            <div class="mb-5 p-4 bg-[#FFFAEB] text-[#B45309] font-bold text-sm border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                <p>⚠️ This will add <span class="black underline decoration-2">ALL AVAILABLE ITEMS</span> from the library. Existing items are skipped safely.</p>
            </div>
            
            <form action="{{ route('panel.items.add.all') }}" method="POST" onsubmit="return confirm('Are you sure you want to add ALL items?');">
                @csrf
                
                <div class="mb-6 relative" id="bulk-char-search-component">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Target Character</label>
                    <input 
                        type="text" 
                        id="bulk-char-search-input" 
                        class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all placeholder:font-normal placeholder:text-gray-400"
                        placeholder="Start typing name..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="character_id" id="bulk-real-char-id" required>
                    <ul id="bulk-char-results-list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                </div>

                <button type="submit" 
                    class="w-full bg-black text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase hover:bg-gray-900 border-white">
                    Insert All Items
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load Data Once
        const allItems = @json($items);
        const allChars = @json($characters);

        function setupSearch(inputId, listId, hiddenId, hiddenHelperId, data, type) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);

            if (!input || !list || !hidden) return;

            function render(results) {
                list.innerHTML = '';
                if (results.length === 0) {
                    list.innerHTML = '<li class="px-4 py-2 text-sm font-bold text-gray-500 border-b-2 border-black bg-gray-50">NO RESULTS FOUND</li>';
                    return;
                }

                results.forEach(obj => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-2 text-sm font-bold text-gray-900 hover:bg-[#A5F3FC] cursor-pointer transition-colors border-b-2 border-black last:border-0';
                    
                    let displayText = '';
                    if (type === 'item') {
                        displayText = `${obj.name} (ID: ${obj.id})`;
                    } else {
                        displayText = `${obj.name} (Lvl ${obj.level})`;
                    }

                    li.textContent = displayText;

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

                const filtered = data.filter(obj => {
                    if (type === 'item') {
                        return (obj.name && obj.name.toLowerCase().includes(val)) || String(obj.id).includes(val);
                    } else {
                        return (obj.name && obj.name.toLowerCase().includes(val));
                    }
                });

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

        setupSearch('char-search-input', 'char-results-list', 'real-char-id', null, allChars, 'char');
        setupSearch('item-search-input', 'item-results-list', 'real-item-id', null, allItems, 'item');
        setupSearch('bulk-char-search-input', 'bulk-char-results-list', 'bulk-real-char-id', null, allChars, 'char');
    });
</script>
@endsection