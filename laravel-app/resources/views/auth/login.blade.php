@extends('layouts.app')
@section('title', 'Login — Barangay SAGIP')

@section('content')
<div class="max-w-sm mx-auto mt-12 bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-bold text-navy mb-1">Barangay SAGIP</h1>
    <p class="text-sm text-gray-500 mb-6">Sign in to your account</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button class="w-full bg-navy text-white rounded-md py-2 font-medium hover:bg-accent transition">
            Sign In
        </button>
    </form>

    <p class="text-sm text-gray-500 mt-4">
        No account yet? <a href="{{ route('register') }}" class="text-accent hover:underline">Register as a resident</a>
    </p>
</div>
@endsection
