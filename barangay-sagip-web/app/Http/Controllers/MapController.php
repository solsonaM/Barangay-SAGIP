<?php

namespace App\Http\Controllers;

use App\Models\EmergencyRequest;
use App\Models\ResponsePersonnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Feature 8: Location Map Generator.
 * The page loads Leaflet and pulls live marker data from the JSON endpoint
 * below so the map can be refreshed without a full page reload.
 */
class MapController extends Controller
{
    public function index(): View
    {
        return view('map.index');
    }

    public function data(): JsonResponse
    {
        $user = Auth::user();

        $requestsQuery = EmergencyRequest::whereNotIn('status', ['resolved', 'cancelled'])
            ->select('id', 'category', 'urgency', 'status', 'latitude', 'longitude');

        // Residents may see the location/status of only their own active
        // requests. Operational personnel and officials may see the full
        // response map.
        if ($user->isResident()) {
            $requestsQuery->where('resident_id', $user->id);
        }

        $requests = $requestsQuery->get();

        $personnel = collect();

        if ($user->isOfficial() || $user->isPersonnel()) {
            $personnel = ResponsePersonnel::where('is_available', true)
                ->select('id', 'name', 'specialization', 'latitude', 'longitude', 'current_workload')
                ->get();
        }

        return response()->json([
            'requests' => $requests,
            'personnel' => $personnel,
        ]);
    }
}
