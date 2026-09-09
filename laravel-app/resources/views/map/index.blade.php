@extends('layouts.app')
@section('title', 'Live Map — Barangay SAGIP')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
<h1 class="text-xl font-bold text-navy mb-4">Live Map</h1>
<p class="text-sm text-gray-500 mb-4">
    Red markers are open requests (darker = more urgent). Blue markers are available response personnel.
    Refreshes automatically every 15 seconds.
</p>
<div id="map" class="w-full h-[32rem] rounded-lg border shadow"></div>

@push('scripts')
<script>
    const map = L.map('map').setView([13.5925, 124.2049], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const urgencyColor = { critical: '#dc2626', high: '#ea580c', average: '#ca8a04', low: '#16a34a' };
    let markers = [];

    function clearMarkers() {
        markers.forEach(m => map.removeLayer(m));
        markers = [];
    }

    function refresh() {
        fetch('{{ route('map.data') }}')
            .then(r => r.json())
            .then(data => {
                clearMarkers();

                data.requests.forEach(req => {
                    const color = urgencyColor[req.urgency] || '#6b7280';
                    const marker = L.circleMarker([req.latitude, req.longitude], {
                        radius: 9, color, fillColor: color, fillOpacity: 0.8
                    }).addTo(map).bindPopup(
                        `<strong>Request #${req.id}</strong><br>${req.category ?? '—'} · ${req.urgency ?? '—'}<br>Status: ${req.status}`
                    );
                    markers.push(marker);
                });

                data.personnel.forEach(p => {
                    const marker = L.circleMarker([p.latitude, p.longitude], {
                        radius: 7, color: '#2563eb', fillColor: '#2563eb', fillOpacity: 0.7
                    }).addTo(map).bindPopup(
                        `<strong>${p.name}</strong><br>${p.specialization}<br>Workload: ${p.current_workload}`
                    );
                    markers.push(marker);
                });
            });
    }

    refresh();
    setInterval(refresh, 15000);
</script>
@endpush
@endsection
