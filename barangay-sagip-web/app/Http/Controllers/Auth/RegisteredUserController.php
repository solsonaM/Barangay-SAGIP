<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handles Feature 1 step one: account creation. Residents complete the
     * rest of their profile (address, household info, etc.) right after,
     * via ResidentProfileController — kept separate so the form isn't
     * overwhelming on first signup.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'regex:/^\p{Lu}[\p{L}\'-]*(\s\p{Lu}[\p{L}\'-]*)*$/u'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^(\p{Lu}[\p{L}\'-]*(\s\p{Lu}[\p{L}\'-]*)*)?$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^\p{Lu}[\p{L}\'-]*(\s\p{Lu}[\p{L}\'-]*)*$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'first_name.regex' => 'First name must start with a capital letter and contain only letters.',
            'middle_name.regex' => 'Middle name must start with a capital letter and contain only letters.',
            'last_name.regex' => 'Last name must start with a capital letter and contain only letters.',
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
        ])));

        $user = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Resident,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('residents.profile.edit')
            ->with('status', 'Account created! Please complete your resident profile.');
    }
}
