@extends('layouts.app')
@section('title', 'Response Personnel — Barangay SAGIP')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-navy">Response Personnel</h1>
    <a href="{{ route('personnel.create') }}" class="bg-navy text-white text-sm rounded-md px-4 py-2 hover:bg-accent transition">
        + Add Personnel
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Specialization</th>
                <th class="px-4 py-2">Active Assignments</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Last Location Update</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($personnel as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">{{ $p->name }}</td>
                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $p->specialization) }}</td>
                    <td class="px-4 py-2">{{ $p->active_assignments_count }}</td>
                    <td class="px-4 py-2">
                        <x-badge :color="$p->is_available ? 'green' : 'gray'">
                            {{ $p->is_available ? 'Available' : 'Unavailable' }}
                        </x-badge>
                    </td>
                    <td class="px-4 py-2 text-gray-500">
                        {{ $p->last_location_update?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('personnel.edit', $p) }}" class="text-accent hover:underline text-sm">Edit</a>
                        <form method="POST" action="{{ route('personnel.toggleAvailability', $p) }}" class="inline">
                            @csrf
                            <button class="text-accent hover:underline text-sm">
                                Mark {{ $p->is_available ? 'Unavailable' : 'Available' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('personnel.destroy', $p) }}"
                              onsubmit="return confirm('Remove {{ $p->name }}? This can\'t be undone.');"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline text-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No personnel registered yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
