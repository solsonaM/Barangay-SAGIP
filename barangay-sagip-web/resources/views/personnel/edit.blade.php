@extends('layouts.app')
@section('title', 'Edit Personnel — Barangay SAGIP')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-xl font-bold text-navy mb-4">Edit {{ $personnel->name }}</h1>

    <form method="POST" action="{{ route('personnel.update', $personnel) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $personnel->name) }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Specialization</label>
            <select name="specialization" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
                @foreach ($specializations as $s)
                    <option value="{{ $s }}" @selected(old('specialization', $personnel->specialization) === $s)>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Phone Number</label>
            <input type="text" name="phone_number" value="{{ old('phone_number', $personnel->phone_number) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_available" value="0">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $personnel->is_available)) class="rounded border-gray-300">
            Available for assignment
        </label>

        <div>
            <label class="block text-sm font-medium mb-1">Base Location (tap map to update)</label>
            <div id="map" class="w-full h-56 rounded-md border"></div>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $personnel->latitude) }}" required>
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $personnel->longitude) }}" required>
            <p class="text-xs text-gray-400 mt-1">Current: {{ $personnel->latitude }}, {{ $personnel->longitude }}</p>
        </div>

        <div class="flex gap-2">
            <button class="bg-navy text-white rounded-md px-6 py-2 text-sm font-medium hover:bg-accent transition">
                Save Changes
            </button>
            <a href="{{ route('personnel.index') }}" class="border border-gray-300 rounded-md px-6 py-2 text-sm font-medium hover:bg-gray-50 transition">
                Cancel
            </a>
        </div>
    </form>

    <form method="POST" action="{{ route('personnel.destroy', $personnel) }}"
          onsubmit="return confirm('Remove {{ $personnel->name }}? This can\'t be undone.');"
          class="mt-4 pt-4 border-t">
        @csrf
        @method('DELETE')
        <button class="text-red-600 text-sm hover:underline">Delete this personnel record</button>
    </form>
</div>

@push('scripts')
<script>
    const startLat = {{ old('latitude', $personnel->latitude) }};
    const startLng = {{ old('longitude', $personnel->longitude) }};
    const map = L.map('map').setView([startLat, startLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let marker = L.marker([startLat, startLng]).addTo(map);
    map.on('click', function (e) {
        document.getElementById('latitude').value = e.latlng.lat.toFixed(7);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(7);
        marker.setLatLng(e.latlng);
    });
</script>
@endpush
@endsection
