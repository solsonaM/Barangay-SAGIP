@extends('layouts.app')
@section('title', 'My Profile — Barangay SAGIP')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-bold text-navy mb-1">Resident Profile</h1>
    <p class="text-sm text-gray-500 mb-6">
        Keeping this up to date helps responders find and identify you faster during an emergency.
    </p>

    <form method="POST" action="{{ route('residents.profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="full_name" value="{{ old('full_name', $profile->full_name ?? '') }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Birthdate</label>
                <input type="date" name="birthdate" value="{{ old('birthdate', optional($profile->birthdate ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Sex</label>
                <select name="sex" class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
                    <option value="">—</option>
                    <option value="male" @selected(old('sex', $profile->sex ?? '') === 'male')>Male</option>
                    <option value="female" @selected(old('sex', $profile->sex ?? '') === 'female')>Female</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Civil Status</label>
                <select name="civil_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
                    @foreach (['single','married','widowed','separated'] as $status)
                        <option value="{{ $status }}" @selected(old('civil_status', $profile->civil_status ?? '') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Purok / Sitio</label>
                <input type="text" name="purok_sitio" value="{{ old('purok_sitio', $profile->purok_sitio ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Address</label>
                <textarea name="address" rows="2" required
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">{{ old('address', $profile->address ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Household Members</label>
                <input type="number" name="household_members_count" min="1"
                       value="{{ old('household_members_count', $profile->household_members_count ?? 1) }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $profile->emergency_contact_name ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Emergency Contact Number</label>
                <input type="text" name="emergency_contact_number" value="{{ old('emergency_contact_number', $profile->emergency_contact_number ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Home Latitude</label>
                <input type="text" name="home_latitude" value="{{ old('home_latitude', $profile->home_latitude ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Home Longitude</label>
                <input type="text" name="home_longitude" value="{{ old('home_longitude', $profile->home_longitude ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
            </div>
        </div>

        <button class="bg-navy text-white rounded-md px-6 py-2 font-medium hover:bg-accent transition">
            Save Profile
        </button>
    </form>
</div>
@endsection
