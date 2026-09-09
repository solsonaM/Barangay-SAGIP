<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreEmergencyRequestRequest;
use App\Models\EmergencyRequest;
use App\Notifications\RequestStatusUpdated;
use App\Services\MLClassificationService;
use App\Services\ResponseAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmergencyRequestController extends Controller
{
    public function __construct(
        protected MLClassificationService $mlService,
        protected ResponseAssignmentService $assignmentService,
    ) {
    }

    /**
     * Feature 2: submission form.
     */
    public function create(): View
    {
        return view('requests.create');
    }

    /**
     * Feature 2 (submit) -> Feature 3 (classify type) -> Feature 4 (classify
     * urgency) -> Feature 5 (validate / flag for review) -> Feature 6
     * (auto-assign when validation passes) -> Feature 7 (status timeline
     * starts here) -> Feature 10 (notify resident).
     */
    public function store(StoreEmergencyRequestRequest $request): RedirectResponse
    {
        $emergencyRequest = new EmergencyRequest($request->validated());
        $emergencyRequest->resident_id = Auth::id();
        $emergencyRequest->status = RequestStatus::Submitted;

        // Features 3 & 4: ML classification (mutates the model in memory)
        $this->mlService->classifyAndApply($emergencyRequest);

        $emergencyRequest->save();

        $emergencyRequest->statusLogs()->create([
            'status' => RequestStatus::Submitted->value,
            'note' => 'Request submitted by resident.',
            'changed_by' => Auth::id(),
        ]);

        // Feature 5: Request Validation
        if ($emergencyRequest->needs_review) {
            $emergencyRequest->transitionTo(
                RequestStatus::NeedsReview,
                $emergencyRequest->review_reason ?? 'Flagged for manual validation.'
            );
        } else {
            $emergencyRequest->transitionTo(RequestStatus::Validated, 'Auto-validated (high classification confidence).');

            // Feature 6: attempt auto-assignment now that it's validated
            $this->assignmentService->autoAssign($emergencyRequest->fresh());
        }

        Auth::user()->notify(new RequestStatusUpdated($emergencyRequest, $emergencyRequest->status->value));

        return redirect()
            ->route('requests.show', $emergencyRequest)
            ->with('status', 'Your request has been submitted.');
    }

    /**
     * Feature 7: Real-time status tracking view (residents see their own
     * request; officials/personnel see any request).
     */
    public function show(EmergencyRequest $emergencyRequest): View
    {
        $this->authorizeView($emergencyRequest);

        $emergencyRequest->load(['statusLogs.changedByUser', 'currentAssignment.responsePersonnel', 'resident']);

        return view('requests.show', ['emergencyRequest' => $emergencyRequest]);
    }

    /**
     * List view: residents see their own; officials/personnel see the
     * barangay-wide queue sorted by urgency then recency (Feature 4 + 11).
     */
    public function index(): View
    {
        $user = Auth::user();

        $query = EmergencyRequest::with(['resident', 'currentAssignment.responsePersonnel']);

        if ($user->isResident()) {
            $query->where('resident_id', $user->id);
        }

        $requests = $query->get()->sortByDesc(function (EmergencyRequest $r) {
            return [$r->urgency?->sortWeight() ?? 0, $r->created_at->timestamp];
        })->values();

        return view('requests.index', ['requests' => $requests]);
    }

    /**
     * Officials/personnel manually advance a request's status
     * (Feature 7 write path).
     */
    public function updateStatus(EmergencyRequest $emergencyRequest, \Illuminate\Http\Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isOfficial() || Auth::user()->isPersonnel(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:validated,assigned,en_route,resolved,cancelled'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $emergencyRequest->transitionTo(
            RequestStatus::from($validated['status']),
            $validated['note'] ?? null,
            Auth::id()
        );

        $emergencyRequest->resident->notify(
            new RequestStatusUpdated($emergencyRequest, $validated['status'])
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
