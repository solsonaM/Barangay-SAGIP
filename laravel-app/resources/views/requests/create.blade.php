@extends('layouts.app')
@section('title', 'Submit a Request — Barangay SAGIP')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
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
            <label class="block text-sm font-medium mb-1">Location (tap the map to set the pin)</label>
            <div id="map" class="w-full h-64 rounded-md border border-gray-300"></div>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" required>
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" required>
            <p id="coords-label" class="text-xs text-gray-400 mt-1">No location selected yet.</p>
        </div>

        <button class="w-full bg-navy text-white rounded-md py-2 font-medium hover:bg-accent transition">
            Submit Request
        </button>
    </form>
</div>

@push('scripts')
<script>
    // Default view centered roughly on the barangay; adjust to your actual coordinates.
    const map = L.map('map').setView([13.5925, 124.2049], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;
    map.on('click', function (e) {
        const { lat, lng } = e.latlng;
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
        document.getElementById('coords-label').innerText = `Selected: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
    });

    // Try to prefill with the browser's geolocation as a starting point.
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            map.setView([pos.coords.latitude, pos.coords.longitude], 16);
        });
    }
</script>
@endpush
@endsection
