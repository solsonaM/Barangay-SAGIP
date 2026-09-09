@extends('layouts.app')
@section('title', 'Dashboard — Barangay SAGIP')

@section('content')

@if($role === 'resident')
    <h1 class="text-xl font-bold text-navy mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-sm text-gray-500 mb-6">Here's the status of your recent requests.</p>

    <div class="flex gap-3 mb-6">
        <a href="{{ route('requests.create') }}" class="bg-navy text-white text-sm rounded-md px-4 py-2 hover:bg-accent transition">
            + Submit a Request
        </a>
        <a href="{{ route('residents.profile.edit') }}" class="border border-gray-300 text-sm rounded-md px-4 py-2 hover:bg-gray-50 transition">
            Edit My Profile
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-2">#</th><th class="px-4 py-2">Category</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Submitted</th><th></th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($requests as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">#{{ $r->id }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $r->category ?? 'pending classification') }}</td>
                        <td class="px-4 py-2"><x-badge :color="$r->status->badgeColor()">{{ $r->status->label() }}</x-badge></td>
                        <td class="px-4 py-2 text-gray-500">{{ $r->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-2 text-right"><a href="{{ route('requests.show', $r) }}" class="text-accent hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">You haven't submitted any requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@elseif($role === 'personnel')
    <h1 class="text-xl font-bold text-navy mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-sm text-gray-500 mb-6">Your active assignments.</p>

    @if(!$personnel)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md p-4">
            Your account isn't linked to a personnel record yet. Ask a barangay official to link it in
            Response Personnel Management.
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-4 py-2">Request</th><th class="px-4 py-2">Category</th><th class="px-4 py-2">Urgency</th><th class="px-4 py-2">Distance</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($assignments as $a)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium">#{{ $a->emergencyRequest->id }}</td>
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $a->emergencyRequest->category) }}</td>
                            <td class="px-4 py-2"><x-badge :color="$a->emergencyRequest->urgency?->badgeColor()">{{ $a->emergencyRequest->urgency?->label() }}</x-badge></td>
                            <td class="px-4 py-2">{{ $a->distance_km ? $a->distance_km . ' km' : '—' }}</td>
                            <td class="px-4 py-2 text-right"><a href="{{ route('requests.show', $a->emergencyRequest) }}" class="text-accent hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No active assignments right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

@else
    {{-- official --}}
    <h1 class="text-xl font-bold text-navy mb-6">Barangay Operations Overview</h1>

    <div class="grid grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">Total Requests</p>
            <p class="text-2xl font-bold text-navy">{{ $counts['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">Needs Review</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $counts['needs_review'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">Critical & Open</p>
            <p class="text-2xl font-bold text-red-600">{{ $counts['critical_open'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">Resolved Today</p>
            <p class="text-2xl font-bold text-green-600">{{ $counts['resolved_today'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500">Available Personnel</p>
            <p class="text-2xl font-bold text-accent">{{ $counts['available_personnel'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 border-b font-semibold text-navy text-sm">Recent Requests</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-2">#</th><th class="px-4 py-2">Resident</th><th class="px-4 py-2">Category</th><th class="px-4 py-2">Urgency</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Assigned</th><th></th></tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($recentRequests as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">#{{ $r->id }}</td>
                        <td class="px-4 py-2">{{ $r->resident->name }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $r->category ?? '—') }}</td>
                        <td class="px-4 py-2">
                            @if($r->urgency)<x-badge :color="$r->urgency->badgeColor()">{{ $r->urgency->label() }}</x-badge>@else — @endif
                        </td>
                        <td class="px-4 py-2"><x-badge :color="$r->status->badgeColor()">{{ $r->status->label() }}</x-badge></td>
                        <td class="px-4 py-2">{{ $r->currentAssignment->responsePersonnel->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-right"><a href="{{ route('requests.show', $r) }}" class="text-accent hover:underline">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
