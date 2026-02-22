@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Daily Rewards</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage attendance rewards for each day.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#FCDC58] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2 uppercase">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        New Schedule Day
    </button>
</div>

<div class="grid grid-cols-1 gap-8">
    
    <!-- LIST -->
    <div class="bg-white border-2 border-black shadow-neo">
        <div class="px-6 py-4 border-b-2 border-black bg-[#FCDC58]">
            <h5 class="text-lg font-black uppercase tracking-widest text-black">Reward Schedule</h5>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-black text-xs uppercase">
                        <th class="p-4 font-black border-r-2 border-black">Day #</th>
                        <th class="p-4 font-black border-r-2 border-black">Reward Value</th>
                        <th class="p-4 font-black">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rewards as $r)
                    <tr class="border-b-2 border-black hover:bg-yellow-50 transition-colors">
                        <td class="p-4 font-black text-lg border-r-2 border-black text-center w-24">
                            {{ $r->price }}
                        </td>
                        <td class="p-4 font-mono font-bold border-r-2 border-black text-blue-800">
                             @if(str_contains($r->item, 'gold_'))
                                <span class="text-yellow-600">Gold {{ str_replace('gold_', '', $r->item) }}</span>
                             @elseif(str_contains($r->item, 'tokens_'))
                                <span class="text-purple-600">Tokens {{ str_replace('tokens_', '', $r->item) }}</span>
                             @elseif(str_contains($r->item, 'tp_'))
                                <span class="text-green-600">TP {{ str_replace('tp_', '', $r->item) }}</span>
                             @else
                                {{ $r->item }}
                             @endif
                        </td>
                        <td class="p-4 flex gap-2">
                            <button onclick="openEditModal('{{ $r->price }}', '{{ $r->item }}')" 
                                class="bg-white text-black px-3 py-1 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] text-xs uppercase transition-all">
                                Edit
                            </button>
                            <form action="{{ route('panel.daily_rewards.delete', $r->id) }}" method="POST" onsubmit="return confirm('Delete this day reward?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 font-bold border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] text-xs uppercase transition-all">
                                    DEL
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    
                    @if($rewards->isEmpty())
                    <tr>
                        <td colspan="3" class="p-8 text-center font-bold text-gray-400 italic">No rewards configured.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeAddModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-md w-full p-0">
            
            <form action="{{ route('panel.daily_rewards.update') }}" method="POST">
                @csrf
                <div class="bg-[#FCDC58] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-black italic tracking-tighter uppercase" id="modal-title">UPDATE REWARD</h3>
                    <button type="button" onclick="closeAddModal()" class="text-black hover:bg-black hover:text-white border-2 border-transparent hover:border-white p-1 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-8 py-8 bg-white">
                    <div class="mb-4">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FCDC58] pl-2">Day Number</label>
                        <input type="number" name="price" id="modal-day" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(252,220,88,1)] transition-all" placeholder="e.g. 1, 2, 3..." required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 border-l-4 border-[#FCDC58] pl-2">Reward Value</label>
                        
                        <!-- Simple type selector for convenience -->
                         <div class="flex gap-2 mb-2">
                            <select id="quick-type" onchange="updateItemString()" class="block w-1/3 text-xs font-bold border-2 border-black px-2 py-1 focus:outline-none">
                                <option value="custom">Custom</option>
                                <option value="gold">Gold</option>
                                <option value="tokens">Tokens</option>
                                <option value="tp">TP</option>
                            </select>
                            <input type="number" id="quick-amount" oninput="updateItemString()" class="block w-2/3 text-xs font-mono border-2 border-black px-2 py-1 hidden" placeholder="Amount">
                        </div>

                        <input type="text" name="item" id="modal-item" class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(252,220,88,1)] transition-all" placeholder="e.g. gold_500, item_id" required>
                        <p class="text-xs text-gray-500 mt-1">Manual: gold_XXX, tokens_XXX, tp_XXX, or ItemID</p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">
                        Save Reward
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
        document.getElementById('modal-title').textContent = 'NEW REWARD';
        document.getElementById('modal-day').value = '';
        document.getElementById('modal-day').readOnly = false;
        document.getElementById('modal-day').classList.remove('bg-gray-100', 'cursor-not-allowed');
        document.getElementById('modal-item').value = '';
        
        document.getElementById('quick-type').value = 'custom';
        toggleQuickInputs();
        
        document.getElementById('addModal').classList.remove('hidden');
    }

    function openEditModal(day, item) {
        document.getElementById('modal-title').textContent = 'EDIT DAY ' + day;
        document.getElementById('modal-day').value = day;
        document.getElementById('modal-day').readOnly = true;
        document.getElementById('modal-day').classList.add('bg-gray-100', 'cursor-not-allowed');
        document.getElementById('modal-item').value = item;
        
        // Try to reverse engineer type
        let type = 'custom';
        let amount = '';
        if (item.startsWith('gold_')) { type = 'gold'; amount = item.replace('gold_', ''); }
        else if (item.startsWith('tokens_')) { type = 'tokens'; amount = item.replace('tokens_', ''); }
        else if (item.startsWith('tp_')) { type = 'tp'; amount = item.replace('tp_', ''); }
        
        document.getElementById('quick-type').value = type;
        document.getElementById('quick-amount').value = amount;
        toggleQuickInputs();

        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function toggleQuickInputs() {
        const type = document.getElementById('quick-type').value;
        const amountInput = document.getElementById('quick-amount');
        if (type === 'custom') {
            amountInput.classList.add('hidden');
        } else {
            amountInput.classList.remove('hidden');
        }
    }

    function updateItemString() {
        const type = document.getElementById('quick-type').value;
        const amount = document.getElementById('quick-amount').value;
        const itemInput = document.getElementById('modal-item');

        toggleQuickInputs();

        if (type !== 'custom' && amount) {
            itemInput.value = `${type}_${amount}`;
        }
    }

    document.getElementById('modal-item').addEventListener('input', function() {
        // If user manually types, switch to custom
        document.getElementById('quick-type').value = 'custom';
        toggleQuickInputs();
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") closeAddModal();
    });
</script>
@endsection
