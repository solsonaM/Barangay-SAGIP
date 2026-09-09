<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Barangay SAGIP')</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    @auth
    <nav class="bg-navy text-white">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg">Barangay SAGIP</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
                <a href="{{ route('requests.index') }}" class="hover:underline">Requests</a>
                <a href="{{ route('map.index') }}" class="hover:underline">Map</a>
                @if(auth()->user()->isOfficial())
                    <a href="{{ route('personnel.index') }}" class="hover:underline">Personnel</a>
                    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                @endif
                <a href="{{ route('notifications.index') }}" class="hover:underline">Notifications</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="hover:underline">Logout</button>
                </form>
            </div>
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
        Barangay SAGIP — Capstone Prototype
    </footer>

    @stack('scripts')
</body>
</html>
