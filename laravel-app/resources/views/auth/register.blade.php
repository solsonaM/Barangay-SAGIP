@extends('layouts.resident-auth')
@section('title', 'Register — Barangay SAGIP')

@section('content')
<div>
    <h1 class="text-2xl font-bold text-white mb-1">Create your account</h1>
    <p class="text-sm text-gray-400 mb-6">Step 1 of 2 — you'll complete your resident profile next.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-1.5">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full rounded-xl bg-field border border-edge text-white px-3.5 py-2.5 text-sm focus:outline-none focus:border-violet focus:ring-1 focus:ring-violet placeholder-gray-500">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-xl bg-field border border-edge text-white px-3.5 py-2.5 text-sm focus:outline-none focus:border-violet focus:ring-1 focus:ring-violet placeholder-gray-500">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-1.5">Phone Number</label>
            <input type="text" name="phone_number" value="{{ old('phone_number') }}" required
                   placeholder="09XXXXXXXXX"
                   class="w-full rounded-xl bg-field border border-edge text-white px-3.5 py-2.5 text-sm focus:outline-none focus:border-violet focus:ring-1 focus:ring-violet placeholder-gray-500">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-1.5">Password</label>
            <input type="password" name="password" required
                   class="w-full rounded-xl bg-field border border-edge text-white px-3.5 py-2.5 text-sm focus:outline-none focus:border-violet focus:ring-1 focus:ring-violet placeholder-gray-500">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-1.5">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-xl bg-field border border-edge text-white px-3.5 py-2.5 text-sm focus:outline-none focus:border-violet focus:ring-1 focus:ring-violet placeholder-gray-500">
        </div>
        <button type="submit" class="w-full bg-gradient-to-r from-violet to-indigo hover:opacity-90 text-white font-semibold py-2.5 rounded-xl transition shadow-lg mt-2">
            Create Account
        </button>
    </form>

    <p class="text-sm text-gray-400 mt-6 text-center">
        Already registered? <a href="{{ route('login') }}" class="text-violet hover:text-indigo font-medium hover:underline">Sign in</a>
    </p>
</div>
@endsection