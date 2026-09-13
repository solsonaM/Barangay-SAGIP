<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateResidentProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Feature 1: Resident Registration and Profiling.
 */
class ResidentProfileController extends Controller
{
    public function edit(): View
    {
        $profile = Auth::user()->residentProfile;

        return view('residents.profile', ['profile' => $profile]);
    }

    public function update(UpdateResidentProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $user->residentProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return redirect()->route('requests.create')->with('status', 'Profile saved. You can now submit a report.');
    }
}
