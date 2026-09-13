<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Barangay SAGIP')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#1F3864',
                        accent: '#2E74B5',
                    }
                }
            }
        }
    </script>
    @stack('head')
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    @auth
    <nav x-data="{ open: false }" class="bg-navy text-white relative">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
            @if(auth()->user()->isResident())
                <a href="{{ route('requests.create') }}" class="font-bold text-lg">Barangay SAGIP</a>

                {{-- Desktop links --}}
                <div class="hidden md:flex items-center gap-4 text-sm">
                    <a href="{{ route('requests.create') }}" class="hover:underline">Report</a>
                    <a href="{{ route('requests.index') }}" class="hover:underline">My Requests</a>
                    <a href="{{ route('notifications.index') }}" class="hover:underline">Notifications</a>
                    <a href="{{ route('residents.profile.edit') }}" class="hover:underline">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="hover:underline">Logout</button>
                    </form>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="open = !open" class="md:hidden p-2 -mr-2" aria-label="Toggle menu">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Mobile menu panel --}}
                <div x-show="open" x-cloak @click.outside="open = false" class="md:hidden absolute top-14 left-0 right-0 bg-navy border-t border-white/10 px-4 py-3 space-y-3 text-sm z-20">
                    <a href="{{ route('requests.create') }}" class="block hover:underline">Report</a>
                    <a href="{{ route('requests.index') }}" class="block hover:underline">My Requests</a>
                    <a href="{{ route('notifications.index') }}" class="block hover:underline">Notifications</a>
                    <a href="{{ route('residents.profile.edit') }}" class="block hover:underline">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="hover:underline">Logout</button>
                    </form>
                </div>
            @else
                <a href="{{ route('dashboard') }}" class="font-bold text-lg">Barangay SAGIP</a>

                {{-- Desktop links --}}
                <div class="hidden md:flex items-center gap-4 text-sm">
                    <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
                    <a href="{{ route('requests.index') }}" class="hover:underline">Requests</a>
                    <a href="{{ route('map.index') }}" class="hover:underline">Map</a>
                    @if(auth()->user()->isOfficial())
                        <a href="{{ route('personnel.index') }}" class="hover:underline">Personnel</a>
                        <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="hover:underline">Notifications</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="hover:underline">Logout</button>
                    </form>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="open = !open" class="md:hidden p-2 -mr-2" aria-label="Toggle menu">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Mobile menu panel --}}
                <div x-show="open" x-cloak @click.outside="open = false" class="md:hidden absolute top-14 left-0 right-0 bg-navy border-t border-white/10 px-4 py-3 space-y-3 text-sm z-20">
                    <a href="{{ route('dashboard') }}" class="block hover:underline">Dashboard</a>
                    <a href="{{ route('requests.index') }}" class="block hover:underline">Requests</a>
                    <a href="{{ route('map.index') }}" class="block hover:underline">Map</a>
                    @if(auth()->user()->isOfficial())
                        <a href="{{ route('personnel.index') }}" class="block hover:underline">Personnel</a>
                        <a href="{{ route('reports.index') }}" class="block hover:underline">Reports</a>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="block hover:underline">Notifications</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="hover:underline">Logout</button>
                    </form>
                </div>
            @endif
        </div>
    </nav>
    @endauth

    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

   <footer class="text-center text-xs text-gray-400 py-4">
        BUILT BY <span class="text-accent font-semibold">MARK ANGELO SOLSONA</span>
    </footer>

    @stack('scripts')
</body>
</html>
