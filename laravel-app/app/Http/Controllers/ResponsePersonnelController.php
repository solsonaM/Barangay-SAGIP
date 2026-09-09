<?php

namespace App\Http\Controllers;

use App\Models\ResponsePersonnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 9: Response Personnel Management.
 * Restricted to officials via the 'role:official' middleware in routes/web.php.
 */
class ResponsePersonnelController extends Controller
{
    public function index(): View
    {
        $personnel = ResponsePersonnel::withCount('activeAssignments')->orderBy('name')->get();

        return view('personnel.index', ['personnel' => $personnel]);
    }

    public function create(): View
    {
        return view('personnel.create', ['specializations' => ResponsePersonnel::specializations()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialization' => ['required', 'in:' . implode(',', ResponsePersonnel::specializations())],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        ResponsePersonnel::create($validated + ['is_available' => true, 'last_location_update' => now()]);

        return redirect()->route('personnel.index')->with('status', 'Personnel added.');
    }

    public function toggleAvailability(ResponsePersonnel $personnel): RedirectResponse
    {
        $personnel->update(['is_available' => ! $personnel->is_available]);

        return back()->with('status', "Marked {$personnel->name} as " . ($personnel->is_available ? 'available' : 'unavailable') . '.');
    }

    public function updateLocation(ResponsePersonnel $personnel, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $personnel->update($validated + ['last_location_update' => now()]);

        return back()->with('status', 'Location updated.');
    }
}
