@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Special Deals</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage special limited-time offers.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#FF90E8] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2 uppercase">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        New Deal
    </button>
</div>

<div class="grid grid-cols-1 gap-8">
    
    <!-- LIST -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#FFDEE9]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Active Special Deals</h5>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-black text-xs uppercase">
                        <th class="p-4 font-black border-r-2 border-black">ID</th>
                        <th class="p-4 font-black border-r-2 border-black">Name</th>
                        <th class="p-4 font-black border-r-2 border-black">Dates</th>
                        <th class="p-4 font-black border-r-2 border-black">Price</th>
                        <th class="p-4 font-black border-r-2 border-black">Rewards</th>
                        <th class="p-4 font-black">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deals as $deal)
                    <tr class="border-b-2 border-black hover:bg-yellow-50 transition-colors">
                        <td class="p-4 font-mono font-bold border-r-2 border-black">{{ $deal->id }}</td>
                        <td class="p-4 font-bold border-r-2 border-black">
                            {{ $deal->name }}
                            <div class="text-xs text-gray-500 font-normal">{{ $deal->description }}</div>
                        </td>
                        <td class="p-4 text-xs font-mono border-r-2 border-black">
                            <span class="block text-green-700">S: {{ $deal->start_time }}</span>
                            <span class="block text-red-700">E: {{ $deal->end_time }}</span>
                        </td>
                        <td class="p-4 font-mono font-bold border-r-2 border-black">{{ $deal->price }} T</td>
                        <td class="p-4 text-xs font-mono border-r-2 border-black max-w-xs truncate">
                            {{ json_encode($deal->rewards) }}
                        </td>
                        <td class="p-4 flex gap-2">
                            <form action="{{ route('panel.special_deals.delete', $deal->id) }}" method="POST" onsubmit="return confirm('Delete this deal?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-[#FF6B6B] text-black px-3 py-1 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] text-xs uppercase transition-all">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    
                    @if($deals->isEmpty())
                    <tr>
                        <td colspan="6" class="p-8 text-center font-bold text-gray-400 italic">No special deals found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t-2 border-black bg-gray-50">
            {{ $deals->links() }}
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-2xl w-full p-0">
            
            <form action="{{ route('panel.special_deals.add') }}" method="POST">
                @csrf
                <div class="bg-[#FF90E8] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-black italic tracking-tighter uppercase">NEW DEAL</h3>
                    <button type="button" onclick="closeAddModal()" class="text-black hover:bg-black hover:text-white border-2 border-transparent hover:border-white p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 bg-white max-h-[70vh] overflow-y-auto">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FF90E8] pl-2">Name</label>
                        <input type="text" name="name" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,144,232,1)] transition-all" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FF90E8] pl-2">Description</label>
                        <textarea name="description" rows="3" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(255,144,232,1)] transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#A5F3FC] pl-2">Start Time</label>
                            <input type="datetime-local" name="start_time" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FF6B6B] pl-2">End Time</label>
                            <input type="datetime-local" name="end_time" class="block w-full text-sm font-bold border-2 border-black px-2 py-2 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FCDC58] pl-2">Price (Tokens)</label>
                        <input type="number" name="price" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(252,220,88,1)] transition-all" required>
                    </div>

                    {{-- DYNAMIC REWARDS UI --}}
                    <div class="mb-6 bg-gray-50 border-2 border-black p-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2">Rewards</label>
                        
                        <div id="reward-list" class="space-y-2 mb-3 empty:hidden"></div>

                        <div class="flex flex-col gap-2">
                             <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-4">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Item ID</label>
                                    <input type="text" id="add-reward-id" class="block w-full text-xs font-mono border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="wpn_001">
                                </div>
                                <div class="col-span-3">
                                    <label class="text-[10px] font-bold uppercase text-gray-500">Qty</label>
                                    <input type="number" id="add-reward-qty" class="block w-full text-xs font-mono border-2 border-black px-2 py-2 focus:outline-none focus:shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] transition-all" placeholder="1">
                                </div>
                                <div class="col-span-5 flex items-end">
                                     <button type="button" onclick="addReward()" class="w-full bg-white text-black text-xs px-2 py-2 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] uppercase transition-all">
                                        + Add
                                    </button>
                                </div>
                             </div>
                        </div>
                        <input type="hidden" name="rewards" id="rewards-input" required>
                    </div>

                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#FF90E8] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
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

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") closeAddModal();
    });

    // --- REWARDS LOGIC ---
    let rewards = [];
    const rewardList = document.getElementById('reward-list');
    const rewardInput = document.getElementById('rewards-input');

    function renderRewards() {
        rewardList.innerHTML = '';
        if (rewards.length === 0) {
            rewardList.classList.add('hidden');
        } else {
            rewardList.classList.remove('hidden');
            rewards.forEach((r, index) => {
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center bg-white p-2 border border-black text-xs';
                
                const span = document.createElement('span');
                span.className = 'font-mono uppercase';
                span.textContent = `${r.item_id} (x${r.qty})`;
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.onclick = () => removeReward(index);
                btn.className = 'text-red-600 font-bold hover:underline px-2';
                btn.textContent = '[DEL]';
                
                div.appendChild(span);
                div.appendChild(btn);
                rewardList.appendChild(div);
            });
        }
        rewardInput.value = JSON.stringify(rewards);
    }

    function addReward() {
        const id = document.getElementById('add-reward-id').value.trim();
        const qty = parseInt(document.getElementById('add-reward-qty').value);

        if (!id) { alert('Enter Item ID'); return; }
        if (!qty || qty < 1) { alert('Enter valid quantity'); return; }

        rewards.push({ item_id: id, qty: qty });
        renderRewards();

        document.getElementById('add-reward-id').value = '';
        document.getElementById('add-reward-qty').value = '';
    }

    function removeReward(index) {
        rewards.splice(index, 1);
        renderRewards();
    }
</script>
@endsection
