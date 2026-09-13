@extends('layouts.app')
@section('title', 'Submit a Request — Barangay SAGIP')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            <label class="block text-sm font-medium mb-1">Your Location</label>
            <div id="map" class="w-full h-64 rounded-md border border-gray-300"></div>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" required>
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" required>
            <div class="flex items-center justify-between mt-1">
                <p id="coords-label" class="text-xs text-gray-400">Requesting your location…</p>
                <button type="button" id="retry-location" class="text-xs text-accent hover:underline hidden">
                    Use my current location
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
    // Default view centered roughly on the barangay until we know the
    // resident's actual location.
    const map = L.map('map').setView([13.5925, 124.2049], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;
    const coordsLabel = document.getElementById('coords-label');
    const retryButton = document.getElementById('retry-location');

    function setLocation(lat, lng, source) {
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
        coordsLabel.innerText = source === 'gps'
            ? `Using your current location (${lat.toFixed(5)}, ${lng.toFixed(5)})`
            : `Location set manually (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
        map.setView([lat, lng], 16);

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
    }

    // Tapping the map always works, as a correction/fallback — GPS can be
    // a little off indoors, or the resident may want to report a location
    // other than where they're currently standing.
    map.on('click', function (e) {
        setLocation(e.latlng.lat, e.latlng.lng, 'manual');
    });

    function requestGps() {
        if (!navigator.geolocation) {
            coordsLabel.innerText = 'Your browser doesn\'t support location detection — tap the map to set your location.';
            retryButton.classList.remove('hidden');
            return;
        }

        coordsLabel.innerText = 'Requesting your location…';
        retryButton.classList.add('hidden');

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                setLocation(pos.coords.latitude, pos.coords.longitude, 'gps');
            },
            function (err) {
                // Permission denied, timed out, or position unavailable —
                // fall back to letting the resident tap the map themselves.
                coordsLabel.innerText = 'Couldn\'t get your location automatically — tap the map to set it, or try again.';
                retryButton.classList.remove('hidden');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    retryButton.addEventListener('click', requestGps);

    // Ask for permission and locate automatically as soon as the page loads.
    requestGps();
</script>
@endpush
@endsection
