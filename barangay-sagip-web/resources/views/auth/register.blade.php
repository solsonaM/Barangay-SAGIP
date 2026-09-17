@extends('layouts.resident-auth')
@section('title', 'Create Account — Barangay SAGIP')

@section('content')
<h2 class="text-2xl font-bold text-white">Create your account</h2>
<p class="mt-1.5 text-sm text-gray-500 mb-8">Step 1 of 2 — your complete home address is required for registration.</p>

<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf

    <x-auth-field label="First Name" name="first_name" :value="old('first_name')" placeholder="e.g. Juan" autofocus class="capitalize-name" />
    <x-auth-field label="Middle Name" name="middle_name" :value="old('middle_name')" :required="false" placeholder="e.g. Padin (optional)" class="capitalize-name" />
    <x-auth-field label="Last Name" name="last_name" :value="old('last_name')" placeholder="e.g. Dela Cruz" class="capitalize-name" />

    <x-auth-field label="Email" name="email" type="email" :value="old('email')" placeholder="you@example.com" />
    <x-auth-field label="Phone Number" name="phone_number" :value="old('phone_number')" placeholder="09XXXXXXXXX" />

    <div>
        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Complete Home Address</label>
        <textarea id="address" name="address" rows="3" required maxlength="500"
                  placeholder="225, Provincial Road, Calatagan Tibang, Virac, Catanduanes"
                  class="w-full rounded-xl border-gray-300 shadow-sm focus:border-violet focus:ring-violet">{{ old('address') }}</textarea>
        <p class="mt-1.5 text-xs text-gray-500">
            Format: House/Unit Number, Street/Road, Barangay, Municipality/City, Province
        </p>
    </div>

    <x-auth-field label="Password" name="password" type="password" placeholder="At least 8 characters" />
    <x-auth-field label="Confirm Password" name="password_confirmation" type="password" placeholder="Re-enter your password" />

    <button class="w-full rounded-xl bg-gradient-to-r from-violet to-azure py-3 font-semibold text-white shadow-lg shadow-violet/20 hover:shadow-violet/30 hover:opacity-95 transition">
        Create Account
    </button>
</form>

<p class="mt-8 text-center text-sm text-gray-500">
    Already registered?
    <a href="{{ route('login') }}" class="text-transparent bg-clip-text bg-gradient-to-r from-violet to-azure font-semibold hover:opacity-80">
        Sign in
    </a>
</p>

@push('scripts')
<script>
    // Auto-capitalize the first letter of each word as the user types their
    // name, so "juan dela cruz" becomes "Juan Dela Cruz" without rejecting
    // the submission — validation on the server is just a safety net.
    document.querySelectorAll('.capitalize-name').forEach(function (input) {
        input.addEventListener('input', function (e) {
            const cursor = e.target.selectionStart;
            e.target.value = e.target.value.replace(/(^|\s)([a-zà-ÿ])/gu, function (match, boundary, letter) {
                return boundary + letter.toUpperCase();
            });
            e.target.setSelectionRange(cursor, cursor);
        });
    });
</script>
@endpush
@endsection
