<?php

namespace App\Services;

use App\Models\EmergencyRequest;
use App\Models\ResponseAssignment;
use App\Models\ResponsePersonnel;
use App\Enums\RequestStatus;

/**
 * Feature 6: Response Assignment Classification.
 *
 * Gathers available response personnel, sends them to the ML service's
 * scoring/ranking endpoint alongside the request, and persists the
 * recommended assignment. Officials can still override the recommendation
 * manually (see ResponseAssignmentController::manualAssign).
 */
class ResponseAssignmentService
{
    public function __construct(protected MLClassificationService $mlService)
    {
    }

    public function autoAssign(EmergencyRequest $request): ?ResponseAssignment
    {
        $candidates = ResponsePersonnel::where('is_available', true)
            ->get()
            ->map(fn (ResponsePersonnel $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'specialization' => $p->specialization,
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'is_available' => $p->is_available,
                'current_workload' => $p->current_workload,
            ])
            ->values()
            ->all();

        if (empty($candidates)) {
            return null;
        }

        $result = $this->mlService->assignResponse($request, $candidates);

        if ($result === null || empty($result['recommended_personnel_id'])) {
            return null;
        }

        $top = collect($result['ranking'])->firstWhere('personnel_id', $result['recommended_personnel_id']);

        $assignment = ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $result['recommended_personnel_id'],
            'assignment_score' => $top['score'] ?? null,
            'distance_km' => $top['distance_km'] ?? null,
            'specialization_match' => $top['specialization_match'] ?? false,
            'was_manual_override' => false,
        ]);

        ResponsePersonnel::where('id', $result['recommended_personnel_id'])
            ->increment('current_workload');

        $request->transitionTo(RequestStatus::Assigned, 'Auto-assigned via ML response-assignment scoring.');

        return $assignment;
    }

    public function manualAssign(EmergencyRequest $request, int $personnelId, int $officialUserId): ResponseAssignment
    {
        $assignment = ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnelId,
            'was_manual_override' => true,
            'assigned_by' => $officialUserId,
        ]);

        ResponsePersonnel::where('id', $personnelId)->increment('current_workload');

        $request->transitionTo(RequestStatus::Assigned, 'Manually assigned by official.', $officialUserId);

        return $assignment;
    }
}
