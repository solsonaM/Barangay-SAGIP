<?php

namespace App\Http\Controllers;

use App\Models\EmergencyRequest;
use App\Models\ResponsePersonnel;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Feature 11: Dashboards.
 *
 * Renders a different summary depending on role:
 *   - resident: their own requests + status
 *   - personnel: their active assignments
 *   - official: barangay-wide operational overview
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if ($user->isResident()) {
            $requests = EmergencyRequest::where('resident_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();

            return view('dashboard', [
                'role' => 'resident',
                'requests' => $requests,
            ]);
        }

        if ($user->isPersonnel()) {
            $personnel = $user->responsePersonnel;
            $assignments = $personnel
                ? $personnel->assignments()->with('emergencyRequest')->whereNull('completed_at')->get()
                : collect();

            return view('dashboard', [
                'role' => 'personnel',
                'personnel' => $personnel,
                'assignments' => $assignments,
            ]);
        }

        // Official / barangay-wide overview
        $counts = [
            'total' => EmergencyRequest::count(),
            'needs_review' => EmergencyRequest::where('needs_review', true)->count(),
            'critical_open' => EmergencyRequest::where('urgency', 'critical')
                ->whereNotIn('status', ['resolved', 'cancelled'])
                ->count(),
            'resolved_today' => EmergencyRequest::where('status', 'resolved')
                ->whereDate('updated_at', today())
                ->count(),
            'available_personnel' => ResponsePersonnel::where('is_available', true)->count(),
        ];

        $categoryBreakdown = EmergencyRequest::selectRaw('category, count(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('total', 'category');

        $recentRequests = EmergencyRequest::with(['resident', 'currentAssignment.responsePersonnel'])
            ->latest()
            ->limit(15)
            ->get();

        return view('dashboard', [
            'role' => 'official',
            'counts' => $counts,
            'categoryBreakdown' => $categoryBreakdown,
            'recentRequests' => $recentRequests,
        ]);
    }
}
