@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Giveaways</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage giveaways and events.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#8B5CF6] text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        CREATE GIVEAWAY
    </button>
</div>

<div class="grid grid-cols-1 gap-8">
    
    <!-- LIST -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#FF90E8]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Giveaways List</h5>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-black text-xs uppercase">
                        <th class="p-4 font-black border-r-2 border-black">Title</th>
                        <th class="p-4 font-black border-r-2 border-black">Dates</th>
                        <th class="p-4 font-black border-r-2 border-black">Prizes</th>
                        <th class="p-4 font-black border-r-2 border-black">Requirements</th>
                        <th class="p-4 font-black border-r-2 border-black">Status</th>
                        <th class="p-4 font-black">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($giveaways as $g)
                    <tr class="border-b-2 border-black hover:bg-gray-50 transition-colors">
                        <td class="p-4 font-bold border-r-2 border-black">
                            {{ $g->title }}
                            <div class="text-xs text-gray-500 font-normal mt-1">{{ Str::limit($g->description, 30) }}</div>
                        </td>
                        <td class="p-4 text-xs font-mono border-r-2 border-black">
                            <span class="block text-green-700">S: {{ $g->start_at }}</span>
                            <span class="block text-red-700">E: {{ $g->end_at }}</span>
                        </td>
                        <td class="p-4 text-xs font-mono border-r-2 border-black max-w-xs">
                             @if($g->prizes)
                                @foreach($g->prizes as $prize)
                                    <div class="bg-blue-100 border border-black px-1 mb-1 inline-block">{{ $prize }}</div>
                                @endforeach
                            @endif
                        </td>
                        <td class="p-4 text-xs font-mono border-r-2 border-black max-w-xs">
                            @if($g->requirements)
                                @foreach($g->requirements as $req)
                                    <div class="bg-yellow-100 border border-black px-1 mb-1 inline-block">{{ $req['name'] ?? $req['type'] }}</div>
                                @endforeach
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="p-4 border-r-2 border-black text-xs font-bold">
                            @if($g->processed)
                                <span class="bg-green-100 text-green-800 px-2 py-1 border border-black transform -rotate-2 inline-block">PROCESSED</span>
                            @else
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 border border-black transform rotate-2 inline-block">PENDING</span>
                            @endif
                        </td>
                        <td class="p-4 flex gap-2">
                            <form action="{{ route('panel.giveaways.delete', $g->id) }}" method="POST" onsubmit="return confirm('Delete giveaway?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-[#FF6B6B] text-black px-3 py-1 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] text-xs uppercase transition-all">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach

                    @if($giveaways->isEmpty())
                    <tr>
                        <td colspan="6" class="p-8 text-center font-bold text-gray-400 italic">No giveaways found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t-2 border-black bg-gray-50">
            {{ $giveaways->links() }}
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-2xl w-full p-0">
            
            <form action="{{ route('panel.giveaways.add') }}" method="POST">
                @csrf
                <div class="bg-[#8B5CF6] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">NEW GIVEAWAY</h3>
                    <button type="button" onclick="closeAddModal()" class="text-white hover:bg-black hover:text-white border-2 border-transparent hover:border-white p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 bg-white max-h-[70vh] overflow-y-auto">
                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#8B5CF6] pl-2">Title</label>
                        <input type="text" name="title" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(139,92,246,1)] transition-all" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#8B5CF6] pl-2">Description</label>
                        <textarea name="description" rows="3" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(139,92,246,1)] transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#A5F3FC] pl-2">Start At</label>
                            <input type="datetime-local" name="start_at" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FF6B6B] pl-2">End At</label>
                            <input type="datetime-local" name="end_at" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                    </div>

                     {{-- PRIZES UI --}}
                     <div class="mb-6 bg-gray-50 border-2 border-black p-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2">Prizes</label>
                        
                        {{-- List of added prizes --}}
                        <div id="prize-list" class="space-y-2 mb-3 empty:hidden"></div>

                        {{-- Add Prize Inputs --}}
                        <div class="flex flex-col gap-2">
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-4">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Type</label>
                                    <select id="prize-type" class="block w-full text-xs font-bold border-2 border-black px-2 py-2 bg-white focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" onchange="togglePrizeInput()">
                                        <option value="item">Item ID</option>
                                        <option value="gold">Gold</option>
                                        <option value="tokens">Tokens</option>
                                        <option value="tp">TP</option>
                                    </select>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-[10px] font-bold uppercase text-gray-500" id="prize-val-label">Value</label>
                                    <input type="text" id="prize-val-text" class="block w-full text-xs font-mono border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="e.g. wpn_001">
                                    <input type="number" id="prize-val-num" class="hidden w-full text-xs font-mono border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="Amount">
                                </div>
                            </div>
                            <button type="button" onclick="addPrize()" class="bg-white lg:w-full w-full text-black text-xs px-4 py-2 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] uppercase transition-all">
                                + Add Prize
                            </button>
                        </div>
                        <input type="hidden" name="prizes" id="prizes-input">
                    </div>

                    {{-- REQUIREMENTS UI --}}
                    <div class="mb-6 bg-gray-50 border-2 border-black p-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2">Requirements</label>
                        
                        {{-- List of added requirements --}}
                        <div id="req-list" class="space-y-2 mb-3 empty:hidden"></div>

                        {{-- Add Requirement Inputs --}}
                        <div class="flex flex-col gap-2">
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-4">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Type</label>
                                    <select id="req-type" class="block w-full text-xs font-bold border-2 border-black px-2 py-2 bg-white focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all">
                                        <option value="level">Min Level</option>
                                        <option value="gold_fee">Gold Fee</option>
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Amount</label>
                                    <input type="number" id="req-amount" class="block w-full text-xs font-mono border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="10">
                                </div>
                                <div class="col-span-5">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Name (Opt)</label>
                                    <input type="text" id="req-name" class="block w-full text-xs font-bold border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="Auto">
                                </div>
                            </div>
                            <button type="button" onclick="addRequirement()" class="bg-white lg:w-full w-full text-black text-xs px-4 py-2 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] uppercase transition-all">
                                + Add Requirement
                            </button>
                        </div>
                        <input type="hidden" name="requirements" id="requirements-input">
                    </div>

                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#8B5CF6] text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Create Giveaway
                    </button>
                    <button type="button" onclick="closeAddModal()" class="w-full sm:w-auto bg-white text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- MODAL LOGIC ---
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeAddModal();
        }
    });

    // --- REQUIREMENTS LOGIC ---
    let requirements = [];
    const reqList = document.getElementById('req-list');
    const reqInput = document.getElementById('requirements-input');

    function renderRequirements() {
        reqList.innerHTML = '';
        if (requirements.length === 0) {
            reqList.classList.add('hidden');
        } else {
            reqList.classList.remove('hidden');
            requirements.forEach((req, index) => {
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center bg-white p-2 border border-black text-xs';
                
                const span = document.createElement('span');
                span.className = 'font-mono';
                
                const typeSpan = document.createElement('span');
                typeSpan.className = 'font-bold uppercase';
                typeSpan.textContent = req.type;
                
                span.appendChild(typeSpan);
                span.appendChild(document.createTextNode(`: ${req.total} (${req.name})`));
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.onclick = () => removeRequirement(index);
                btn.className = 'text-red-600 font-bold hover:underline px-2';
                btn.textContent = '[DEL]';
                
                div.appendChild(span);
                div.appendChild(btn);
                reqList.appendChild(div);
            });
        }
        reqInput.value = JSON.stringify(requirements);
    }

    function addRequirement() {
        const type = document.getElementById('req-type').value;
        const total = parseInt(document.getElementById('req-amount').value);
        let name = document.getElementById('req-name').value;

        if (!total || total <= 0) {
            alert('Please enter a valid amount.');
            return;
        }

        if (!name) {
            if (type === 'level') name = `Minimum Level ${total}`;
            else if (type === 'gold_fee') name = `Join Fee: ${total} Gold`;
        }

        requirements.push({ name, type, total });
        renderRequirements();
        
        // Reset inputs
        document.getElementById('req-amount').value = '';
        document.getElementById('req-name').value = '';
    }

    function removeRequirement(index) {
        requirements.splice(index, 1);
        renderRequirements();
    }

    // --- PRIZES LOGIC ---
    let prizes = [];
    const prizeList = document.getElementById('prize-list');
    const prizeInput = document.getElementById('prizes-input');

    function togglePrizeInput() {
        const type = document.getElementById('prize-type').value;
        const textInput = document.getElementById('prize-val-text');
        const numInput = document.getElementById('prize-val-num');
        const label = document.getElementById('prize-val-label');

        if (type === 'item') {
            textInput.classList.remove('hidden');
            numInput.classList.add('hidden');
            label.textContent = 'Item ID';
        } else {
            textInput.classList.add('hidden');
            numInput.classList.remove('hidden');
            label.textContent = 'Amount';
        }
    }

    function renderPrizes() {
        prizeList.innerHTML = '';
        if (prizes.length === 0) {
            prizeList.classList.add('hidden');
        } else {
            prizeList.classList.remove('hidden');
            prizes.forEach((p, index) => {
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center bg-white p-2 border border-black text-xs';
                
                const span = document.createElement('span');
                span.className = 'font-mono text-blue-600 font-bold';
                span.textContent = p;
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.onclick = () => removePrize(index);
                btn.className = 'text-red-600 font-bold hover:underline px-2';
                btn.textContent = '[DEL]';
                
                div.appendChild(span);
                div.appendChild(btn);
                prizeList.appendChild(div);
            });
        }
        prizeInput.value = JSON.stringify(prizes);
    }

    function addPrize() {
        const type = document.getElementById('prize-type').value;
        let val;

        if (type === 'item') {
            val = document.getElementById('prize-val-text').value.trim();
            if (!val) { alert('Enter Item ID'); return; }
        } else {
            const num = document.getElementById('prize-val-num').value;
            if (!num || num <= 0) { alert('Enter valid amount'); return; }
            val = `${type}_${num}`;
        }

        prizes.push(val);
        renderPrizes();

        // Reset
        document.getElementById('prize-val-text').value = '';
        document.getElementById('prize-val-num').value = '';
    }

    function removePrize(index) {
        prizes.splice(index, 1);
        renderPrizes();
    }
</script>
@endsection
