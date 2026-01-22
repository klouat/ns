@extends('panel.layout')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Mail System</h1>
    <p class="mt-1 text-sm text-gray-500">Send official mail and rewards to players.</p>
</div>

<div class="max-w-6xl mx-auto">
    
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm relative">
        <div class="px-6 py-4 border-b border-gray-100 bg-indigo-50/50 rounded-t-xl flex items-center">
            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            <h5 class="font-semibold text-indigo-900">Send Official Mail</h5>
        </div>

        <div class="p-6">
            <form action="{{ route('panel.mail.send') }}" method="POST" id="mailForm">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div class="border-b lg:border-b-0 lg:border-r border-gray-100 pb-6 lg:pb-0 lg:pr-8">
                        
                        <div class="mb-6 relative" id="char-search-component">
                            <label class="block mb-2 text-sm font-semibold text-gray-800">1. Select Target Character</label>
                            
                            <input 
                                type="text" 
                                id="char-search-input" 
                                class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                placeholder="Type character name..."
                                autocomplete="off"
                            >
                            <input type="hidden" name="character_id" id="real-char-id" required>

                            <ul id="char-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></ul>
                            
                            <p class="mt-1 text-xs text-gray-500" id="char-helper-text">Start typing to search...</p>
                        </div>

                        <hr class="border-gray-100 my-6">

                        <div class="mb-5">
                            <label for="title" class="block mb-2 text-sm font-semibold text-gray-800">2. Mail Content</label>
                            <input type="text" id="title" name="title" required
                                class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Subject / Title">
                        </div>

                        <div class="mb-5">
                            <textarea id="body" name="body" rows="5" required
                                class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Message body..."></textarea>
                        </div>

                        <div class="mb-5">
                            <label for="type" class="block mb-2 text-sm font-medium text-gray-700">Mail Type</label>
                            <select id="type" name="type" required
                                class="block w-full px-4 py-2.5 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="4" selected>Reward Mail (Small Icons)</option>
                                <option value="5">Reward Mail (Big Icons)</option>
                                <option value="1">Normal (No Rewards Visible)</option>
                                <option value="2">Friend Request</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Types 4 and 5 are best for showing attached items.</p>
                        </div>
                    </div>

                    <div class="lg:pl-2">
                        <label class="block mb-3 text-sm font-semibold text-gray-800">3. Attach Rewards</label>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-5">
                            
                            <div class="mb-4">
                                <label class="block mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">Category</label>
                                <select id="rewardCategory" onchange="toggleRewardInputs()"
                                    class="block w-full px-3 py-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="item">Item (Weapon/Back/Set/Material)</option>
                                    <option value="skill">Skill</option>
                                    <option value="token">Tokens</option>
                                    <option value="gold">Gold</option>
                                    <option value="xp">Experience (XP)</option>
                                    <option value="tp">Training Points (TP)</option>
                                    <option value="ss">Secret Study (SS)</option>
                                    <option value="prestige">Prestige</option>
                                    <option value="merit">Merit</option>
                                </select>
                            </div>

                            <div id="rewardItemContainer" class="mb-4 relative">
                                <label class="block mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">Search Item</label>
                                <input type="text" id="item-search-input" 
                                    class="block w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Type item name..." autocomplete="off">
                                <input type="hidden" id="rewardItemId"> <ul id="item-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></ul>
                            </div>

                            <div id="rewardSkillContainer" class="mb-4 relative hidden">
                                <label class="block mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">Search Skill</label>
                                <input type="text" id="skill-search-input" 
                                    class="block w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Type skill name..." autocomplete="off">
                                <input type="hidden" id="rewardSkillId"> <ul id="skill-results-list" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></ul>
                            </div>

                            <div id="rewardQuantityContainer" class="mb-4">
                                <label class="block mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">Quantity</label>
                                <input type="number" id="rewardAmount" value="1" min="1"
                                    class="block w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <button type="button" onclick="addRewardToList()"
                                class="w-full flex justify-center items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add to Attachment
                            </button>
                        </div>

                        <label class="block mb-2 text-xs font-medium text-gray-500 uppercase tracking-wide">Attached Rewards:</label>
                        <div id="rewardList" class="bg-white border border-gray-200 rounded-lg p-3 min-h-[150px] max-h-[250px] overflow-y-auto mb-4">
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 text-sm">
                                <span>No rewards attached yet.</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="rewards" id="finalRewards">
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-100 pt-6">
                    <button type="submit" id="submitBtn"
                        class="w-full sm:w-auto px-8 py-3 text-base font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 shadow-md transition-all flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Send Mail
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // 1. DATA LOADING
        // ==========================================
        const allItems = @json($items);
        const allSkills = @json($skills);
        const allChars = @json($characters);

        // ==========================================
        // 2. SETUP DROPDOWNS (Reusable Function)
        // ==========================================
        function setupSearch(inputId, listId, hiddenId, helperId, data, type) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);
            const helper = document.getElementById(helperId); // Optional

            if (!input || !list || !hidden) return;

            function render(results) {
                list.innerHTML = '';
                if (results.length === 0) {
                    list.innerHTML = '<li class="px-4 py-2 text-sm text-gray-500">No results found</li>';
                    return;
                }
                results.forEach(obj => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer transition-colors border-b border-gray-100 last:border-0';
                    
                    let displayText = '';
                    if (type === 'char') displayText = `${obj.name} (Lvl ${obj.level})`;
                    else displayText = `${obj.name} (ID: ${obj.id})`;

                    li.textContent = displayText;
                    li.addEventListener('click', () => {
                        input.value = obj.name; 
                        hidden.value = obj.id;
                        list.classList.add('hidden');
                        if(helper) helper.textContent = `Selected ID: ${obj.id}`;
                    });
                    list.appendChild(li);
                });
            }

            input.addEventListener('input', function(e) {
                const val = e.target.value.toLowerCase();
                if (val.length < 1) { list.classList.add('hidden'); return; }
                const filtered = data.filter(obj => 
                    (obj.name && obj.name.toLowerCase().includes(val)) || String(obj.id).includes(val)
                );
                render(filtered.slice(0, 10));
                list.classList.remove('hidden');
            });

            input.addEventListener('focus', function() {
                if (input.value === '') { render(data.slice(0, 10)); list.classList.remove('hidden'); }
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !list.contains(e.target)) { list.classList.add('hidden'); }
            });
        }

        // Initialize Dropdowns
        setupSearch('char-search-input', 'char-results-list', 'real-char-id', 'char-helper-text', allChars, 'char');
        setupSearch('item-search-input', 'item-results-list', 'rewardItemId', null, allItems, 'item');
        setupSearch('skill-search-input', 'skill-results-list', 'rewardSkillId', null, allSkills, 'skill');
    });

    // ==========================================
    // 3. REWARD LOGIC (Global Scope)
    // ==========================================
    let attachedRewards = [];

    function toggleRewardInputs() {
        const category = document.getElementById('rewardCategory').value;
        const itemCont = document.getElementById('rewardItemContainer');
        const skillCont = document.getElementById('rewardSkillContainer');
        const qtyCont = document.getElementById('rewardQuantityContainer');

        // Reset display
        itemCont.classList.add('hidden');
        skillCont.classList.add('hidden');
        qtyCont.classList.remove('hidden');

        if (category === 'item') {
            itemCont.classList.remove('hidden');
        } else if (category === 'skill') {
            skillCont.classList.remove('hidden');
            qtyCont.classList.add('hidden'); // No quantity for skills usually
        }
    }

    function addRewardToList() {
        const category = document.getElementById('rewardCategory').value;
        const amount = parseInt(document.getElementById('rewardAmount').value) || 1;
        
        let id = '';
        let label = '';

        if (category === 'item') {
            id = document.getElementById('rewardItemId').value;
            label = document.getElementById('item-search-input').value;
            if(!id) { alert('Please select an item first.'); return; }
            
            // Check stackable (Simplified logic: if type is material/item/essential)
            // You might need to adjust this logic based on your exact ID structure
            const type = id.split('_')[0]; 
            const stackable = ['material', 'item', 'essential'].includes(type);

            if (stackable) {
                attachedRewards.push({ str: id + ":" + amount, label: label, amount: amount, category: category });
            } else {
                for(let i=0; i<amount; i++) {
                    attachedRewards.push({ str: id, label: label, amount: 1, category: category });
                }
            }

        } else if (category === 'skill') {
            id = document.getElementById('rewardSkillId').value;
            label = document.getElementById('skill-search-input').value;
            if(!id) { alert('Please select a skill first.'); return; }
            
            attachedRewards.push({ str: id, label: label, amount: 1, category: category });

        } else {
            // Currencies (gold, token, etc.)
            id = category;
            label = category.charAt(0).toUpperCase() + category.slice(1); // "Gold"
            attachedRewards.push({ str: category + "_" + amount, label: label, amount: amount, category: category });
        }

        renderRewards();
        // Clear inputs after add
        document.getElementById('item-search-input').value = '';
        document.getElementById('rewardItemId').value = '';
        document.getElementById('skill-search-input').value = '';
        document.getElementById('rewardSkillId').value = '';
        document.getElementById('rewardAmount').value = 1;
    }

    function removeReward(index) {
        attachedRewards.splice(index, 1);
        renderRewards();
    }

    function renderRewards() {
        const list = document.getElementById('rewardList');
        const finalInput = document.getElementById('finalRewards');
        list.innerHTML = '';

        if (attachedRewards.length === 0) {
            list.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-400 text-sm"><span>No rewards attached yet.</span></div>';
            finalInput.value = '';
            return;
        }

        let rewardStrings = [];
        attachedRewards.forEach((reward, index) => {
            rewardStrings.push(reward.str);
            
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center p-2 mb-1 border-b border-gray-100 last:border-0 bg-gray-50 rounded';
            div.innerHTML = `
                <div class="text-sm text-gray-700">
                    <span class="font-medium">${reward.label}</span> 
                    ${reward.category !== 'skill' ? `<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">x${reward.amount}</span>` : ''}
                </div>
                <button type="button" onclick="removeReward(${index})" class="text-red-500 hover:text-red-700 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            list.appendChild(div);
        });

        finalInput.value = rewardStrings.join(',');
    }
</script>
@endsection