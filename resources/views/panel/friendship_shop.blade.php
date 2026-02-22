@extends('panel.layout')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Friendship Shop</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Manage items purchasable with Friendship Points.</p>
    </div>
    <button onclick="openAddModal()" 
        class="bg-[#10B981] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[4px] hover:translate-y-[4px] transition-all flex items-center gap-2 uppercase">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Add Item
    </button>
</div>

<!-- List -->
<div class="bg-white border-2 border-black shadow-neo">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-sm font-black uppercase text-black bg-[#6EE7B7] border-b-2 border-black">
                <tr>
                    <th class="px-6 py-4 border-r-2 border-black">Item</th>
                    <th class="px-6 py-4 border-r-2 border-black">Price (FP)</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-black">
                @php
                    $itemMap = collect($allItems)->pluck('name', 'id')->toArray();
                @endphp
                @foreach($items as $item)
                <tr class="hover:bg-[#ECFDF5] transition-colors font-medium">
                    <td class="px-6 py-4 border-r-2 border-black">
                        <div class="font-bold text-gray-900">{{ $itemMap[$item->item] ?? $item->item }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ $item->item }}</div>
                    </td>
                    <td class="px-6 py-4 border-r-2 border-black font-mono font-bold text-green-700">
                        {{ number_format($item->price) }} FP
                    </td>
                    <td class="px-6 py-4 text-right">
                         <div class="flex justify-end gap-2">
                             <button onclick="openEditModal('{{ $item->id }}', '{{ $item->item }}', '{{ $item->price }}')" 
                                class="text-blue-600 font-bold hover:underline uppercase text-xs">Edit</button>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('panel.friendship_shop.delete', $item->id) }}" method="POST" onsubmit="return confirm('Delete this item?');">
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
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-xl w-full p-0">
             <form action="{{ route('panel.friendship_shop.add') }}" method="POST">
                @csrf
                <div class="bg-[#10B981] px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-black italic tracking-tighter uppercase">NEW ITEM</h3>
                    <button type="button" onclick="closeAddModal()" class="text-black hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-8 bg-white space-y-6">
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Item ID</label>
                        <input type="text" name="item_id" required class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(16,185,129,1)] transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Price (Friendship Points)</label>
                        <input type="number" name="price" value="100" required class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(16,185,129,1)] transition-all">
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-5 border-t-2 border-black flex flex-row-reverse gap-4">
                    <button type="submit" class="w-full sm:w-auto bg-[#10B981] text-black px-6 py-3 font-bold border-2 border-black shadow-neo hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all uppercase">Add Item</button>
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
        <div class="relative bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] text-left overflow-hidden transform transition-all sm:max-w-xl w-full p-0">
             <form action="{{ route('panel.friendship_shop.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                
                <div class="bg-black px-6 py-5 border-b-2 border-black flex justify-between items-center">
                    <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">UPDATE ITEM</h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-300 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-8 bg-white space-y-6">
                    <div>
                        <label class="block text-sm font-black uppercase text-gray-400 mb-2 pl-2">Item ID (Locked)</label>
                        <input type="text" id="edit_item_id" class="block w-full text-sm font-bold border-2 border-gray-300 bg-gray-100 px-4 py-3 cursor-not-allowed" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-black uppercase text-gray-900 mb-2 pl-2 border-l-4 border-black">Price (Friendship Points)</label>
                        <input type="number" name="price" id="edit_price" required class="block w-full text-sm font-bold border-2 border-black px-4 py-3 focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all">
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
    function openAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
    function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }

    function openEditModal(id, itemId, price) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_item_id').value = itemId;
        document.getElementById('edit_price').value = price;
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
