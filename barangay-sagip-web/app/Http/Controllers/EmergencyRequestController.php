<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreEmergencyRequestRequest;
use App\Models\EmergencyRequest;
use App\Models\ResponsePersonnel;
use App\Notifications\RequestStatusUpdated;
use App\Services\TokenizationClassificationService;
use App\Services\ResponseAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class EmergencyRequestController extends Controller
{
    public function __construct(
        protected TokenizationClassificationService $mlService,
        protected ResponseAssignmentService $assignmentService,
    ) {
    }

    public function create(): View
    {
        return view('requests.create');
    }

    public function store(StoreEmergencyRequestRequest $request): RedirectResponse
    {
        $emergencyRequest = new EmergencyRequest($request->validated());
        $emergencyRequest->resident_id = Auth::id();
        $emergencyRequest->status = RequestStatus::Submitted;

        $this->mlService->classifyAndApply($emergencyRequest);

        $emergencyRequest->save();

        $emergencyRequest->statusLogs()->create([
            'status' => RequestStatus::Submitted->value,
            'note' => 'Request submitted by resident.',
            'changed_by' => Auth::id(),
        ]);

        if ($emergencyRequest->needs_review) {
            $emergencyRequest->transitionTo(
                RequestStatus::NeedsReview,
                $emergencyRequest->review_reason ?? 'Flagged for manual validation.'
            );
        } else {
            $emergencyRequest->transitionTo(RequestStatus::Validated, 'Auto-validated (high classification confidence).');
            $this->assignmentService->autoAssign($emergencyRequest->fresh());
        }

        Auth::user()->notify(new RequestStatusUpdated($emergencyRequest, $emergencyRequest->status->value));

        return redirect()
            ->route('requests.show', $emergencyRequest)
            ->with('status', 'Your request has been submitted.');
    }

    public function show(EmergencyRequest $emergencyRequest): View
    {
        $this->authorizeView($emergencyRequest);

        $emergencyRequest->load(['statusLogs.changedByUser', 'currentAssignment.responsePersonnel', 'resident']);

        return view('requests.show', ['emergencyRequest' => $emergencyRequest]);
    }

    public function index(): View
    {
        $user = Auth::user();

        $query = EmergencyRequest::with(['resident', 'currentAssignment.responsePersonnel']);

        if ($user->isResident()) {
            $query->where('resident_id', $user->id);
        }

        $requests = $query
            ->orderByRaw("CASE urgency\n                WHEN 'critical' THEN 4\n                WHEN 'high' THEN 3\n                WHEN 'average' THEN 2\n                WHEN 'low' THEN 1\n                ELSE 0\n            END DESC")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('requests.index', ['requests' => $requests]);
    }

    /**
     * Officials/personnel manually advance a request's status.
     * Resolving or cancelling an active request also closes its assignment
     * and releases the responder's workload slot.
     */
    public function updateStatus(EmergencyRequest $emergencyRequest, Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isOfficial() || Auth::user()->isPersonnel(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:validated,assigned,en_route,resolved,cancelled'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = RequestStatus::from($validated['status']);

        DB::transaction(function () use ($emergencyRequest, $newStatus, $validated) {
            $lockedRequest = EmergencyRequest::whereKey($emergencyRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedRequest->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => sprintf(
                        'Request cannot transition from %s to %s.',
                        $lockedRequest->status->label(),
                        $newStatus->label()
                    ),
                ]);
            }

            if (in_array($newStatus, [RequestStatus::Resolved, RequestStatus::Cancelled], true)) {
                $assignment = $lockedRequest->currentAssignment()->lockForUpdate()->first();

                if ($assignment !== null) {
                    $personnel = $assignment->responsePersonnel()->lockForUpdate()->first();

                    $assignment->update(['completed_at' => now()]);

                    if ($personnel !== null) {
                        ResponsePersonnel::whereKey($personnel->id)
                            ->where('current_workload', '>', 0)
                            ->decrement('current_workload');
                    }
                }
            }

            $lockedRequest->transitionTo(
                $newStatus,
                $validated['note'] ?? null,
                Auth::id()
            );
        });

        $emergencyRequest->refresh();
        $emergencyRequest->resident->notify(
            new RequestStatusUpdated($emergencyRequest, $newStatus->value)
        );

        return back()->with('status', 'Status updated.');
    }

    protected function authorizeView(EmergencyRequest $emergencyRequest): void
    {
        $user = Auth::user();

        abort_unless(
            $user->isOfficial() || $user->isPersonnel() || $emergencyRequest->resident_id === $user->id,
            403
        );
    }
}
