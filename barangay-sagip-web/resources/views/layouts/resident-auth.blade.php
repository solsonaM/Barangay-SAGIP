<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Barangay SAGIP')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Manrope', 'sans-serif'] },
                    colors: {
                        base: '#0A0A12',
                        panel: '#13131F',
                        field: '#1B1C2A',
                        edge: '#2A2B3D',
                        violet: '#7C3AED',
                        indigo: '#4F46E5',
                        azure: '#3B82F6',
                    },
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Manrope', sans-serif; }
        .brand-glow {
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
            opacity: 0.55;
        }
        @media (prefers-reduced-motion: no-preference) {
            .brand-glow--drift { animation: drift 14s ease-in-out infinite; }
        }
        @keyframes drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(24px, -18px) scale(1.08); }
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #1B1C2A inset !important;
            -webkit-text-fill-color: #F3F4F6 !important;
            caret-color: #F3F4F6;
            transition: background-color 9999s ease-in-out 0s;
        }
    </style>
    @stack('head')
</head>
<body class="bg-base text-gray-100 min-h-screen">
    <div class="min-h-screen lg:flex">

        {{-- Left: branding panel — hidden on small screens to keep the form reachable without scrolling on a phone --}}
        <div class="relative hidden lg:flex lg:w-[46%] overflow-hidden bg-[#0A0A12] bg-cover bg-top" style="background-image: url('{{ asset('images/sagip-hero.jpg') }}')">
            <div class="absolute inset-0 bg-gradient-to-b from-black/25 via-black/50 to-black/85"></div>

            <div class="relative z-10 flex flex-col justify-center h-full p-10 xl:p-12 w-full">
                <div>
                    <h1 class="text-3xl xl:text-4xl font-extrabold leading-tight text-white max-w-sm">
                        Help is one report away.
                    </h1>
                    <p class="mt-4 text-gray-300 max-w-sm leading-relaxed">
                        The emergency and assistance line for Barangay Calatagan Tibang, Virac, Catanduanes —
                        submit a report and get matched to the right responder in seconds.
                    </p>
                </div>

                <div class="space-y-4 mt-10">
                    @foreach ([
                        ['Report in your own words', 'No forms to fill out mid-emergency — just describe what\'s happening.'],
                        ['Matched automatically', 'Your report is classified and routed to the right responder.'],
                        ['Tracked in real time', 'Follow your request from submitted to resolved.'],
                    ] as [$title, $body])
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-gradient-to-br from-violet to-azure"></span>
                            <div>
                                <p class="text-sm font-semibold text-gray-100">{{ $title }}</p>
                                <p class="text-sm text-gray-400">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: form panel --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">

                <div class="lg:hidden flex items-center justify-center mb-10">
                    <img src="{{ asset('images/sagip-logo.png') }}" alt="Barangay SAGIP" class="h-11 w-auto rounded-md">
                </div>

                @if (session('status'))
                    <div class="mb-5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 text-sm">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
