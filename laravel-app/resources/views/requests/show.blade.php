@extends('layouts.app')
@section('title', 'Request #' . $emergencyRequest->id . ' — Barangay SAGIP')

@section('content')
<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-navy">Request #{{ $emergencyRequest->id }}</h1>
                <p class="text-sm text-gray-500">
                    Submitted {{ $emergencyRequest->created_at->format('M j, Y g:i A') }}
                    by {{ $emergencyRequest->resident->name }}
                </p>
            </div>
            <div class="flex gap-2">
                @if($emergencyRequest->urgency)
                    <x-badge :color="$emergencyRequest->urgency->badgeColor()">{{ $emergencyRequest->urgency->label() }} Urgency</x-badge>
                @endif
                <x-badge :color="$emergencyRequest->status->badgeColor()">{{ $emergencyRequest->status->label() }}</x-badge>
            </div>
        </div>

        <p class="bg-gray-50 rounded-md p-3 text-sm mb-4">{{ $emergencyRequest->description }}</p>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <span class="text-gray-500">Category:</span>
                <span class="font-medium capitalize">{{ str_replace('_', ' ', $emergencyRequest->category ?? '—') }}</span>
                @if($emergencyRequest->category_confidence)
                    <span class="text-gray-400">({{ number_format($emergencyRequest->category_confidence * 100, 1) }}% confidence)</span>
                @endif
            </div>
            <div>
                <span class="text-gray-500">Urgency:</span>
                <span class="font-medium capitalize">{{ $emergencyRequest->urgency?->label() ?? '—' }}</span>
                @if($emergencyRequest->urgency_confidence)
                    <span class="text-gray-400">({{ number_format($emergencyRequest->urgency_confidence * 100, 1) }}% confidence)</span>
                @endif
            </div>
        </div>

        @if($emergencyRequest->needs_review)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md p-3 mb-6">
                <strong>Flagged for review:</strong> {{ $emergencyRequest->review_reason }}
            </div>
        @endif

        @if($assignment = $emergencyRequest->currentAssignment)
            <div class="bg-indigo-50 border border-indigo-200 rounded-md p-3 mb-6 text-sm">
                <strong>Assigned to:</strong> {{ $assignment->responsePersonnel->name }}
                ({{ str_replace('_', ' ', $assignment->responsePersonnel->specialization) }})
                @if($assignment->distance_km)
                    — {{ $assignment->distance_km }} km away
                @endif
                @if($assignment->was_manual_override)
                    <span class="text-gray-400">(manually assigned)</span>
                @endif
            </div>
        @endif

        <h2 class="font-semibold text-navy mb-2">Status Timeline</h2>
        <ol class="relative border-l border-gray-200 ml-2">
            @foreach ($emergencyRequest->statusLogs as $log)
                <li class="mb-4 ml-4">
                    <div class="absolute w-2 h-2 bg-accent rounded-full -left-1 mt-1.5"></div>
                    <time class="text-xs text-gray-400">{{ $log->created_at->format('M j, g:i A') }}</time>
                    <p class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $log->status) }}</p>
                    @if($log->note)
                        <p class="text-xs text-gray-500">{{ $log->note }}</p>
                    @endif
                </li>
            @endforeach
        </ol>

        @if(auth()->user()->isOfficial() || auth()->user()->isPersonnel())
            <div class="border-t pt-4 mt-4">
                <h2 class="font-semibold text-navy mb-2">Update Status</h2>
                <form method="POST" action="{{ route('requests.updateStatus', $emergencyRequest) }}" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="rounded-md border-gray-300 text-sm">
                        @foreach (['validated','assigned','en_route','resolved','cancelled'] as $status)
                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" placeholder="Optional note" class="flex-1 rounded-md border-gray-300 text-sm">
                    <button class="bg-navy text-white text-sm rounded-md px-4 hover:bg-accent transition">Update</button>
                </form>

                @if(auth()->user()->isOfficial())
                    <a href="{{ route('requests.assign.edit', $emergencyRequest) }}" class="inline-block mt-3 text-accent text-sm hover:underline">
                        Review / reassign responder →
                    </a>
                @endif
            </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold text-navy mb-2 text-sm">Location</h2>
        <div id="detail-map" class="w-full h-56 rounded-md border"></div>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
@push('scripts')
<script>
    const dmap = L.map('detail-map').setView([{{ $emergencyRequest->latitude }}, {{ $emergencyRequest->longitude }}], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(dmap);
    L.marker([{{ $emergencyRequest->latitude }}, {{ $emergencyRequest->longitude }}]).addTo(dmap);
</script>
@endpush
@endsection
