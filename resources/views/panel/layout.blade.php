<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ninja Saga Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased selection:bg-indigo-500 selection:text-white">

    <aside class="fixed top-0 left-0 z-40 w-64 h-screen bg-white border-r border-gray-200 transition-transform -translate-x-full sm:translate-x-0">
        <div class="h-full px-4 py-8 overflow-y-auto">
            <a href="#" class="flex items-center pl-2 mb-10">
                <span class="self-center text-xl font-bold whitespace-nowrap text-gray-900 tracking-tight">
                    NinjaSaga <span class="text-indigo-600">Panel</span>
                </span>
            </a>

            <ul class="space-y-2 font-medium">
                <li>
                    <a href="{{ route('panel.characters') }}" 
                       class="flex items-center p-3 rounded-lg group transition-all duration-200 
                       {{ request()->routeIs('panel.characters') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <span class="ml-1">Character Panel</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('panel.skills') }}" 
                       class="flex items-center p-3 rounded-lg group transition-all duration-200 
                       {{ request()->routeIs('panel.skills') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <span class="ml-1">Skills Panel</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('panel.items') }}" 
                       class="flex items-center p-3 rounded-lg group transition-all duration-200 
                       {{ request()->routeIs('panel.items') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <span class="ml-1">Items Panel</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('panel.mail') }}" 
                       class="flex items-center p-3 rounded-lg group transition-all duration-200 
                       {{ request()->routeIs('panel.mail') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <span class="ml-1">Mail Panel</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <div class="p-4 sm:ml-64 min-h-screen bg-gray-50">
        <div class="p-4 mt-4 max-w-7xl mx-auto">
            
            @if(session('success'))
                <div class="flex items-center p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                    </svg>
                    <div>
                        <span class="font-medium">Success:</span> {{ session('success') }}
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8" onclick="this.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-center p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 shadow-sm" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
                    </svg>
                    <div>
                        <span class="font-medium">Error:</span> {{ session('error') }}
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8" onclick="this.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            @endif

            @yield('content')
            
        </div>
    </div>

</body>
</html>