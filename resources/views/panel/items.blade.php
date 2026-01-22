@extends('panel.layout')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Item Management</h1>
    <p class="mt-1 text-sm text-gray-500">Manage character inventory and bulk actions.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm relative">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h5 class="font-semibold text-gray-900">Add Single Item</h5>
        </div>
        
        <div class="p-6">
            <form action="{{ route('panel.items.add') }}" method="POST">
                @csrf
                
                <div class="mb-5 relative" id="char-search-component">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Select Character</label>
                    
                    <input 
                        type="text" 
                        id="char-search-input" 
                        class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="Type character name..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="character_id" id="real-char-id" required>

                    <ul id="char-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></ul>
                    
                    <p class="mt-1 text-xs text-gray-500" id="char-helper-text">Start typing to search character...</p>
                </div>

                <div class="mb-6 relative" id="item-search-component">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Select Item</label>
                    
                    <input 
                        type="text" 
                        id="item-search-input" 
                        class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="Type item name or ID..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="item_id" id="real-item-id" required>

                    <ul id="item-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></ul>
                    
                    <p class="mt-1 text-xs text-gray-500" id="item-helper-text">Start typing to search item...</p>
                </div>

                <button type="submit" 
                    class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200 shadow-sm">
                    Add Item
                </button>
            </form>
        </div>
    </div>

  <div class="bg-white border border-gray-200 rounded-xl shadow-sm relative">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
        <h5 class="font-semibold text-gray-900">Bulk Actions</h5>
    </div>
    
    <div class="p-6">
        <div class="mb-5 p-4 bg-yellow-50 text-yellow-800 text-sm rounded-lg border border-yellow-100">
            <p>This will add <span class="font-semibold">all available items</span> from the library to the selected character. Existing items will be skipped.</p>
        </div>
        
        <form action="{{ route('panel.items.add.all') }}" method="POST" onsubmit="return confirm('Are you sure you want to add ALL items?');">
            @csrf
            
            <div class="mb-6 relative" id="bulk-char-search-component">
                <label class="block mb-2 text-sm font-medium text-gray-700">Select Character</label>
                
                <input 
                    type="text" 
                    id="bulk-char-search-input" 
                    class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                    placeholder="Type character name..."
                    autocomplete="off"
                >
                <input type="hidden" name="character_id" id="bulk-real-char-id" required>

                <ul id="bulk-char-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></ul>
                
                <p class="mt-1 text-xs text-gray-500" id="bulk-char-helper-text">Start typing to search...</p>
            </div>

            <button type="submit" 
                class="w-full text-white bg-amber-500 hover:bg-amber-600 focus:ring-4 focus:ring-amber-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-all duration-200 shadow-sm">
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

        /**
         * Generic Function to Setup a Searchable Dropdown
         * @param {string} inputId - ID of the text input
         * @param {string} listId - ID of the UL list
         * @param {string} hiddenId - ID of the hidden input
         * @param {string} helperId - ID of the helper text p tag
         * @param {Array} data - The data array (items or chars)
         * @param {string} type - 'item' or 'char' (defines how we filter/display)
         */
        function setupSearch(inputId, listId, hiddenId, helperId, data, type) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);
            const helper = document.getElementById(helperId);

            if (!input || !list || !hidden) return; // Guard clause

            // Render function
            function render(results) {
                list.innerHTML = '';
                if (results.length === 0) {
                    list.innerHTML = '<li class="px-4 py-2 text-sm text-gray-500">No results found</li>';
                    return;
                }

                results.forEach(obj => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer transition-colors border-b border-gray-100 last:border-0';
                    
                    // Display Text Logic
                    let displayText = '';
                    if (type === 'item') {
                        displayText = `${obj.name} (ID: ${obj.id})`;
                    } else {
                        displayText = `${obj.name} (Lvl ${obj.level})`;
                    }

                    li.textContent = displayText;

                    // Click Handler
                    li.addEventListener('click', () => {
                        input.value = obj.name;   // Show Name
                        hidden.value = obj.id;    // Save ID
                        list.classList.add('hidden');
                        if(helper) helper.textContent = `Selected ID: ${obj.id}`;
                    });

                    list.appendChild(li);
                });
            }

            // Input Handler
            input.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase();
                
                if (val.length < 1) {
                    list.classList.add('hidden');
                    return;
                }

                // Filter Logic
                const filtered = data.filter(obj => {
                    if (type === 'item') {
                        return (obj.name && obj.name.toLowerCase().includes(val)) || String(obj.id).includes(val);
                    } else {
                        return (obj.name && obj.name.toLowerCase().includes(val));
                    }
                });

                // Limit to 10 results to prevent lag
                render(filtered.slice(0, 10));
                list.classList.remove('hidden');
            });

            // Focus Handler (Show recent/top 10)
            input.addEventListener('focus', function() {
                if (input.value === '') {
                    render(data.slice(0, 10));
                    list.classList.remove('hidden');
                }
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.add('hidden');
                }
            });
        }

        // --- INITIALIZE ALL 3 DROPDOWNS ---

        // 1. Single Item - Character Select
        setupSearch('char-search-input', 'char-results-list', 'real-char-id', 'char-helper-text', allChars, 'char');

        // 2. Single Item - Item Select
        setupSearch('item-search-input', 'item-results-list', 'real-item-id', 'item-helper-text', allItems, 'item');

        // 3. Bulk Item - Character Select
        setupSearch('bulk-char-search-input', 'bulk-char-results-list', 'bulk-real-char-id', 'bulk-char-helper-text', allChars, 'char');
    });
</script>
@endsection