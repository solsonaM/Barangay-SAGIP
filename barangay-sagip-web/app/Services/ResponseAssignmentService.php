<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\EmergencyRequest;
use App\Models\ResponseAssignment;
use App\Models\ResponsePersonnel;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

/**
 * Feature 6: Response Assignment Classification.
 *
 * Gathers available response personnel, sends them to the ML service's
 * scoring/ranking endpoint alongside the request, and persists the
 * recommended assignment. Officials can still override the recommendation
 * manually (see ResponseAssignmentController::store()).
 */
class ResponseAssignmentService
{
    public function __construct(
        protected TokenizationClassificationService $mlService,
        protected DatabaseManager $database,
    ) {
    }

    public function autoAssign(EmergencyRequest $request): ?ResponseAssignment
    {
        $candidates = ResponsePersonnel::where('is_available', true)
            ->whereDoesntHave('activeAssignments')
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
        $personnelId = (int) $result['recommended_personnel_id'];

        $assignment = $this->database->transaction(function () use ($request, $personnelId, $top) {
            $lockedRequest = EmergencyRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== RequestStatus::Validated || $lockedRequest->currentAssignment()->exists()) {
                return null;
            }

            $personnel = ResponsePersonnel::whereKey($personnelId)->lockForUpdate()->first();

            if ($personnel === null || ! $personnel->is_available || $personnel->activeAssignments()->exists()) {
                return null;
            }

            $assignment = ResponseAssignment::create([
                'emergency_request_id' => $lockedRequest->id,
                'response_personnel_id' => $personnel->id,
                'assignment_score' => $top['score'] ?? null,
                'distance_km' => $top['distance_km'] ?? null,
                'specialization_match' => $top['specialization_match'] ?? false,
                'was_manual_override' => false,
            ]);

            $personnel->increment('current_workload');

            $lockedRequest->transitionTo(RequestStatus::Assigned, 'Auto-assigned via ML response-assignment scoring.');

            return $assignment;
        });

        if ($assignment !== null) {
            $assignment->load('responsePersonnel.user', 'emergencyRequest');
            $assignment->responsePersonnel?->user?->notify(new NewAssignmentNotification($assignment));
        }

        return $assignment;
    }

    public function manualAssign(EmergencyRequest $request, int $personnelId, int $officialUserId): ResponseAssignment
    {
        return $this->database->transaction(function () use ($request, $personnelId, $officialUserId) {
            $lockedRequest = EmergencyRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedRequest->status, [RequestStatus::Validated, RequestStatus::Assigned], true)) {
                throw ValidationException::withMessages([
                    'response_personnel_id' => 'This request cannot be assigned in its current status.',
                ]);
            }

            $personnel = ResponsePersonnel::whereKey($personnelId)->lockForUpdate()->first();

            if ($personnel === null) {
                throw ValidationException::withMessages([
                    'response_personnel_id' => 'The selected responder does not exist.',
                ]);
            }

            if (! $personnel->is_available) {
                throw ValidationException::withMessages([
                    'response_personnel_id' => 'The selected responder is currently unavailable.',
                ]);
            }

            $currentAssignment = $lockedRequest->currentAssignment()->lockForUpdate()->first();

            if ($currentAssignment?->response_personnel_id === $personnel->id) {
                throw ValidationException::withMessages([
                    'response_personnel_id' => 'This responder is already assigned to the request.',
                ]);
            }

            if ($personnel->activeAssignments()->exists()) {
                throw ValidationException::withMessages([
                    'response_personnel_id' => 'The selected responder is already handling another active request.',
                ]);
            }

            // A manual override replaces the existing active assignment.
            if ($currentAssignment !== null) {
                $oldPersonnel = ResponsePersonnel::whereKey($currentAssignment->response_personnel_id)
                    ->lockForUpdate()
                    ->first();

                $currentAssignment->update(['completed_at' => now()]);

                if ($oldPersonnel !== null) {
                    ResponsePersonnel::whereKey($oldPersonnel->id)
                        ->where('current_workload', '>', 0)
                        ->decrement('current_workload');
                }
            }

            $assignment = ResponseAssignment::create([
                'emergency_request_id' => $lockedRequest->id,
                'response_personnel_id' => $personnel->id,
                'was_manual_override' => true,
                'assigned_by' => $officialUserId,
            ]);

            $personnel->increment('current_workload');

            if ($lockedRequest->status !== RequestStatus::Assigned) {
                $lockedRequest->transitionTo(RequestStatus::Assigned, 'Manually assigned by official.', $officialUserId);
            }

            return $assignment;
        });
    }
}
