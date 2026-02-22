@extends('panel.layout')

@section('content')

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Mail System</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Send in-game messages and rewards to players.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto">
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#FFDA55]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Compose Mail</h5>
        </div>
        
        <div class="p-8">
            <form action="{{ route('panel.mail.send') }}" method="POST">
                @csrf
                
                <div class="mb-6 relative" id="char-search-component">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Recipient Character</label>
                    <input 
                        type="text" 
                        id="char-search-input" 
                        class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all placeholder:font-normal placeholder:text-gray-400"
                        placeholder="Start typing name..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="character_id" id="real-char-id" required>
                    <ul id="char-results-list" class="absolute z-50 w-full mt-1 bg-white border-2 border-black shadow-neo max-h-60 overflow-y-auto hidden"></ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Sender Name</label>
                        <input type="text" name="sender_name" value="Admin" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Mail Type</label>
                        <select name="type" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all">
                            <option value="0">Normal Message</option>
                            <option value="5">System / Reward</option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Subject Line</label>
                    <input type="text" name="title" placeholder="Enter title..." required class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all placeholder:font-normal placeholder:text-gray-400">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Message Body</label>
                    <textarea name="body" rows="4" required class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all placeholder:font-normal placeholder:text-gray-400" placeholder="Type your message here..."></textarea>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Rewards (Optional)</label>
                    <div class="relative">
                        <input type="text" name="rewards" placeholder="Format: item:1001:1,gold:500 (Use helper below)" 
                            class="block w-full text-sm font-mono font-bold border-2 border-black px-4 py-3 bg-gray-50 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,218,85,1)] transition-all placeholder:text-gray-400">
                    </div>
                    
                    <!-- Reward Helper -->
                    <div class="mt-4 p-4 bg-gray-100 border-2 border-black border-dashed">
                        <p class="text-xs font-black uppercase text-gray-500 mb-2">Reward String Helper</p>
                        <div class="flex flex-wrap gap-2 text-xs">
                             <span class="px-2 py-1 bg-white border border-black font-bold font-mono">skill:[ID]</span>
                            <span class="px-2 py-1 bg-white border border-black font-bold font-mono">item:[ID]:[QTY]</span>
                            <span class="px-2 py-1 bg-white border border-black font-bold font-mono">gold:[QTY]</span>
                            <span class="px-2 py-1 bg-white border border-black font-bold font-mono">token:[QTY]</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                        class="w-full md:w-auto bg-[#FFDA55] text-black px-8 py-4 font-black border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all uppercase text-lg">
                        Send Mail
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const allChars = @json($characters);

        function setupSearch(inputId, listId, hiddenId, hiddenHelperId, data) {
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
                    li.className = 'px-4 py-2 text-sm font-bold text-gray-900 hover:bg-[#FFDA55] cursor-pointer transition-colors border-b-2 border-black last:border-0';
                    li.textContent = `${obj.name} (Lvl ${obj.level})`;

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
                    (obj.name && obj.name.toLowerCase().includes(val))
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

        setupSearch('char-search-input', 'char-results-list', 'real-char-id', null, allChars);
    });
</script>
@endsection