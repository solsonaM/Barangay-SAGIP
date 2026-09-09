@extends('layouts.app')
@section('title', 'Assign Responder — Request #' . $emergencyRequest->id)

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-xl font-bold text-navy mb-1">Assign Responder — Request #{{ $emergencyRequest->id }}</h1>
    <p class="text-sm text-gray-500 mb-4 capitalize">
        {{ str_replace('_', ' ', $emergencyRequest->category) }} · {{ $emergencyRequest->urgency?->label() }} urgency
    </p>

    @if($current = $emergencyRequest->currentAssignment)
        <div class="bg-indigo-50 border border-indigo-200 rounded-md p-3 mb-4 text-sm">
            Currently assigned to <strong>{{ $current->responsePersonnel->name }}</strong>
            @if($current->assignment_score)
                (ML-recommended, score {{ $current->assignment_score }})
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('requests.assign.store', $emergencyRequest) }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium mb-1">Select a responder</label>
        <div class="space-y-2">
            @foreach ($availablePersonnel as $p)
                <label class="flex items-center gap-3 border rounded-md p-3 cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="response_personnel_id" value="{{ $p->id }}" required>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ $p->name }}
                            @if($p->specialization === $emergencyRequest->category)
                                <x-badge color="green">specialization match</x-badge>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 capitalize">
                            {{ str_replace('_', ' ', $p->specialization) }} · workload: {{ $p->current_workload }}
                        </p>
                    </div>
                </label>
            @endforeach
        </div>
        <button class="bg-navy text-white rounded-md px-6 py-2 text-sm font-medium hover:bg-accent transition">
            Confirm Assignment
        </button>
    </form>
</div>
@endsection
