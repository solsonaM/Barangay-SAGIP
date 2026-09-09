@extends('layouts.app')
@section('title', 'Notifications — Barangay SAGIP')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-navy">Notifications</h1>
    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button class="text-sm text-accent hover:underline">Mark all as read</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow divide-y">
    @forelse ($notifications as $n)
        <div class="p-4 flex items-start justify-between {{ $n->read_at ? 'opacity-60' : '' }}">
            <div>
                <p class="text-sm">{{ $n->data['message'] ?? 'Notification' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
            </div>
            @unless($n->read_at)
                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                    @csrf
                    <button class="text-xs text-accent hover:underline">Mark read</button>
                </form>
            @endunless
        </div>
    @empty
        <p class="p-6 text-center text-gray-400 text-sm">No notifications yet.</p>
    @endforelse
</div>

<div class="mt-4">
    {{ $notifications->links() }}
</div>
@endsection
