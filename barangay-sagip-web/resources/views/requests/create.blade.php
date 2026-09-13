@extends('layouts.app')
@section('title', 'Submit a Request — Barangay SAGIP')

@push('head')
<style>
    @keyframes radar-ping {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.3); opacity: 0.2; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
    .animate-radar {
        animation: radar-ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto">

    @unless(auth()->user()->residentProfile)
        <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-md p-3 mb-4">
            Tip: <a href="{{ route('residents.profile.edit') }}" class="underline font-medium">complete your resident profile</a>
            so responders can identify and reach you faster — but don't let that stop you from reporting now.
        </div>
    @endunless

    <div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-bold text-navy mb-1">Submit an Emergency / Assistance Request</h1>
    <p class="text-sm text-gray-500 mb-6">
        Describe what's happening in your own words — our system will automatically classify the type
        and urgency of your request and route it to the right responder.
    </p>

    <form method="POST" action="{{ route('requests.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">What's happening?</label>
            <textarea name="description" rows="4" required minlength="5" maxlength="2000"
                      placeholder="e.g. Sunog po sa bahay namin, malaki na ang apoy..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Your Location</label>
            
            <div class="relative w-full h-72 rounded-xl overflow-hidden border border-gray-300 shadow-inner bg-gray-900">
                <div id="gps-loading-overlay" class="absolute inset-0 z-30 bg-gray-900/90 backdrop-blur-sm flex flex-col items-center justify-center text-white p-6 transition-opacity duration-500">
                    <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full border-2 border-indigo-500/30 animate-radar"></div>
                        <div class="absolute inset-0 rounded-full border-2 border-t-indigo-500 border-r-transparent border-b-indigo-500/50 border-l-transparent animate-spin"></div>
                        <svg class="w-6 h-6 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div id="loading-title" class="text-sm font-mono tracking-widest uppercase text-indigo-300 animate-pulse">Acquiring GPS Signal...</div>
                    <div id="loading-subtitle" class="text-xs text-gray-400 mt-1">Triangulating satellite coordinates</div>
                </div>

                <iframe id="satellite-map" class="w-full h-full border-0 z-10" src="about:blank" allowfullscreen="" loading="lazy"></iframe>

                <div class="absolute bottom-3 left-3 right-3 z-20 bg-white/95 backdrop-blur-md p-3 rounded-lg shadow-lg border border-gray-100 flex items-center space-x-3">
                    <div class="bg-indigo-600 text-white p-2 rounded-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Locked Location</p>
                        <p id="address-display" class="text-xs text-gray-700 truncate">Detecting location...</p>
                    </div>
                </div>
            </div>

            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" required>
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" required>
            
            <div class="flex items-center justify-between mt-2">
                <p id="coords-label" class="text-xs text-gray-500">Location is automatically acquired via device GPS in satellite imagery view.</p>
                <button type="button" id="retry-location" class="text-xs text-indigo-600 hover:underline font-medium hidden">
                    Retry GPS Location
                </button>
            </div>
        </div>

        <button class="w-full bg-navy text-white rounded-md py-2 font-medium hover:bg-accent transition">
            Submit Request
        </button>
    </form>
    </div>
</div>

@push('scripts')
<script>
    const satelliteMap = document.getElementById('satellite-map');
    const coordsLabel = document.getElementById('coords-label');
    const retryButton = document.getElementById('retry-location');
    const loadingOverlay = document.getElementById('gps-loading-overlay');
    const addressDisplay = document.getElementById('address-display');
    const loadingTitle = document.getElementById('loading-title');
    const loadingSubtitle = document.getElementById('loading-subtitle');

    function fetchAddress(lat, lng) {
        addressDisplay.innerText = 'Resolving street address...';
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                const address = data.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                addressDisplay.innerText = address;
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 400);
            })
            .catch(() => {
                addressDisplay.innerText = `Coordinates: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 400);
            });
    }

    function setLocation(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
        
        satelliteMap.src = `https://maps.google.com/maps?q=${lat},${lng}&t=k&z=19&output=embed`;

        fetchAddress(lat, lng);
    }

    function requestGps() {
        if (!navigator.geolocation) {
            loadingOverlay.style.display = 'none';
            addressDisplay.innerText = 'Geolocation not supported';
            retryButton.classList.remove('hidden');
            return;
        }

        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.opacity = '1';
        loadingTitle.innerText = 'Acquiring GPS Signal...';
        loadingSubtitle.innerText = 'Triangulating satellite coordinates';
        retryButton.classList.add('hidden');

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                setLocation(pos.coords.latitude, pos.coords.longitude);
            },
            function (err) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 400);
                addressDisplay.innerText = 'Unable to fetch precise location automatically';
                retryButton.classList.remove('hidden');
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    retryButton.addEventListener('click', requestGps);

    requestGps();
</script>
@endpush
@endsection