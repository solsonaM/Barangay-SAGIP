@extends('layouts.app')
@section('title', 'Register — Barangay SAGIP')

@section('content')
<div class="max-w-sm mx-auto mt-12 bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-bold text-navy mb-1">Create your account</h1>
    <p class="text-sm text-gray-500 mb-6">Step 1 of 2 — you'll complete your resident profile next.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Phone Number</label>
            <input type="text" name="phone_number" value="{{ old('phone_number') }}" required
                   placeholder="09XXXXXXXXX"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <button class="w-full bg-navy text-white rounded-md py-2 font-medium hover:bg-accent transition">
            Create Account
        </button>
    </form>

    <p class="text-sm text-gray-500 mt-4">
        Already registered? <a href="{{ route('login') }}" class="text-accent hover:underline">Sign in</a>
    </p>
</div>
@endsection
