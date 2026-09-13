@extends('layouts.resident-auth')
@section('title', 'Staff Login — Barangay SAGIP')

@section('content')
<p class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-1">Staff Access</p>
<h2 class="text-2xl font-bold text-white">Barangay SAGIP</h2>
<p class="mt-1.5 text-sm text-gray-500 mb-8">Sign in as a barangay official or response personnel.</p>

<form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
    @csrf

    <x-auth-field label="Email" name="email" type="email" :value="old('email')" placeholder="you@example.com" autofocus />
    <x-auth-field label="Password" name="password" type="password" placeholder="Enter your password" />

    <label class="flex items-center gap-2 text-sm text-gray-400">
        <input type="checkbox" name="remember" class="rounded border-edge bg-field text-violet focus:ring-violet/60">
        Remember me
    </label>

    <button class="w-full rounded-xl bg-gradient-to-r from-violet to-azure py-3 font-semibold text-white shadow-lg shadow-violet/20 hover:shadow-violet/30 hover:opacity-95 transition">
        Sign In
    </button>
</form>
@endsection
