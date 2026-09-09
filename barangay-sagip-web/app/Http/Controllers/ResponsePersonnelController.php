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

    public function edit(ResponsePersonnel $personnel): View
    {
        return view('personnel.edit', [
            'personnel' => $personnel,
            'specializations' => ResponsePersonnel::specializations(),
        ]);
    }

    public function update(ResponsePersonnel $personnel, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialization' => ['required', 'in:' . implode(',', ResponsePersonnel::specializations())],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $validated['is_available'] = $request->boolean('is_available');
        $validated['last_location_update'] = now();

        $personnel->update($validated);

        return redirect()->route('personnel.index')->with('status', "{$personnel->name} updated.");
    }

    public function destroy(ResponsePersonnel $personnel): RedirectResponse
    {
        // Guard against deleting someone with an open assignment — resolve or
        // reassign it first so a live request isn't left pointing at a
        // deleted responder.
        if ($personnel->activeAssignments()->exists()) {
            return back()->with('status', "Can't delete {$personnel->name} — they have an active assignment. Resolve or reassign it first.");
        }

        $name = $personnel->name;
        $personnel->delete();

        return redirect()->route('personnel.index')->with('status', "{$name} removed.");
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
