<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Public Sans"', 'sans-serif'],
                    },
                    boxShadow: {
                        'neo': '4px 4px 0px 0px rgba(0,0,0,1)',
                        'neo-sm': '2px 2px 0px 0px rgba(0,0,0,1)',
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #EFEEF6; }
        /* Neo-brutalist scrollbar */
        ::-webkit-scrollbar { width: 12px; }
        ::-webkit-scrollbar-track { background: #fff; border-left: 2px solid #000; }
        ::-webkit-scrollbar-thumb { background: #000; border: 2px solid #fff; }
        ::-webkit-scrollbar-thumb:hover { background: #333; }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-[#E0E7FF] min-h-screen">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r-2 border-black fixed h-full z-30 flex flex-col justify-between">
        <div class="h-16 flex-shrink-0 flex items-center px-6 border-b-2 border-black bg-[#FFDA55]">
            <span class="text-xl font-black tracking-tighter uppercase italic">Admin Panel</span>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
            <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Core</p>
            
            <a href="{{ route('panel.characters') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.characters') ? 'bg-[#FF90E8] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Characters</span>
            </a>
            
            <a href="{{ route('panel.mail') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.mail') ? 'bg-[#FFDA55] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Mail System</span>
            </a>

            <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-widest mt-6 mb-2">Game Data</p>

            <a href="{{ route('panel.items') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.items') ? 'bg-[#5EF1D5] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Item Library</span>
            </a>

            <a href="{{ route('panel.skills') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.skills') ? 'bg-[#5EF1D5] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Skill Library</span>
            </a>

            <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-widest mt-6 mb-2">Shop</p>

            <a href="{{ route('panel.limited_store') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.limited_store') ? 'bg-[#FFDA55] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Limited Store</span>
            </a>

            <a href="{{ route('panel.hunting_house') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.hunting_house') ? 'bg-[#FFDA55] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Hunting House</span>
            </a>

            <a href="{{ route('panel.material_market') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.material_market') ? 'bg-[#FFDA55] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Material Market</span>
            </a>

            <a href="{{ route('panel.friendship_shop') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.friendship_shop') ? 'bg-[#FFDA55] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Friendship Shop</span>
            </a>

            <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-widest mt-6 mb-2">Events & Rewards</p>

            <a href="{{ route('panel.special_deals') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.special_deals') ? 'bg-[#FF90E8] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Special Deals</span>
            </a>

            <a href="{{ route('panel.giveaways') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.giveaways') ? 'bg-[#FF90E8] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Giveaways</span>
            </a>

            <a href="{{ route('panel.daily_rewards') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.daily_rewards') ? 'bg-[#FCDC58] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Daily Rewards</span>
            </a>

            <p class="px-3 text-xs font-bold text-gray-500 uppercase tracking-widest mt-6 mb-2">System</p>

            <a href="{{ route('panel.server_time') }}" 
                class="flex items-center p-3 text-sm font-bold border-2 border-black transition-all
                {{ request()->routeIs('panel.server_time') ? 'bg-[#5EF1D5] shadow-neo translate-x-[-2px] translate-y-[-2px]' : 'bg-white hover:bg-gray-50 hover:shadow-neo-sm' }}">
                <span>Server Time</span>
            </a>
            
            <!-- Bottom padding to prevent last item being stuck to edge -->
            <div class="h-4"></div>
        </nav>

        <div class="p-4 border-t-2 border-black bg-gray-50 flex-shrink-0">
            <div class="text-xs font-bold font-mono">
                System Status: <span class="text-green-600 bg-green-100 border border-green-600 px-1">ONLINE</span>
            </div>
            <div class="text-xs font-bold mt-1 font-mono">
                v1.0.4-NEO
            </div>
        </div>
    </aside>

    <!-- Content -->
    <main class="flex-1 ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            @if(session('success'))
                <div class="mb-6 p-4 bg-[#5EF1D5] border-2 border-black shadow-neo flex items-center justify-between">
                    <div class="font-bold flex items-center gap-2">
                        <span class="text-xl">✅</span>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-[#FF6B6B] border-2 border-black shadow-neo flex items-center justify-between text-white">
                    <div class="font-bold flex items-center gap-2">
                        <span class="text-xl">⚠️</span>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-[#FF6B6B] border-2 border-black shadow-neo">
                    <ul class="list-disc list-inside font-bold text-white">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

</body>
</html>