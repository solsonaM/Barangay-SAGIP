<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\EmergencyRequest;
use App\Models\ResponseAssignment;
use App\Models\ResponsePersonnel;
use App\Models\User;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createRequest(User $resident, RequestStatus $status): EmergencyRequest
    {
        return EmergencyRequest::create([
            'resident_id' => $resident->id,
            'description' => 'Test emergency request.',
            'category' => 'general_assistance',
            'category_confidence' => 0.95,
            'urgency' => 'average',
            'urgency_confidence' => 0.95,
            'needs_review' => false,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
            'status' => $status,
        ]);
    }

    public function test_official_can_update_request_status(): void
    {
        Notification::fake();

        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Validated);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Assigned->value,
                'note' => 'Responder assigned.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Assigned->value,
        ]);

        $this->assertDatabaseHas('request_status_logs', [
            'emergency_request_id' => $request->id,
            'status' => RequestStatus::Assigned->value,
            'changed_by' => $official->id,
        ]);

        Notification::assertSentTo(
            $resident,
            RequestStatusUpdated::class,
            fn (RequestStatusUpdated $notification) =>
                $notification->emergencyRequest->id === $request->id
                && $notification->newStatus === RequestStatus::Assigned->value
        );
    }

    public function test_official_can_validate_a_request_flagged_for_review(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::NeedsReview);
        $request->update([
            'needs_review' => true,
            'review_reason' => 'Low classification confidence.',
        ]);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Validated->value,
                'note' => 'Official reviewed and validated.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Validated->value,
        ]);
    }

    public function test_personnel_cannot_validate_a_request_flagged_for_review(): void
    {
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $personnel = ResponsePersonnel::create([
            'user_id' => $personnelUser->id,
            'name' => 'Assigned Responder',
            'specialization' => 'general_assistance',
            'is_available' => true,
            'current_workload' => 0,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
        ]);
        $request = $this->createRequest($resident, RequestStatus::NeedsReview);
        $request->update([
            'needs_review' => true,
            'review_reason' => 'Low classification confidence.',
        ]);

        $this->actingAs($personnelUser)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Validated->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::NeedsReview->value,
        ]);

        $this->assertDatabaseHas('response_personnel', [
            'id' => $personnel->id,
            'current_workload' => 0,
        ]);
    }

    public function test_resident_cannot_update_request_status(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Validated);

        $this->actingAs($resident)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Cancelled->value,
            ])
            ->assertForbidden();
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Resolved);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Assigned->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Resolved->value,
        ]);
    }

    public function test_request_cannot_skip_assignment_before_en_route(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Validated);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::EnRoute->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Validated->value,
        ]);
    }

    public function test_personnel_can_update_only_an_assigned_request(): void
    {
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $personnel = ResponsePersonnel::create([
            'user_id' => $personnelUser->id,
            'name' => 'Assigned Responder',
            'specialization' => 'general_assistance',
            'is_available' => true,
            'current_workload' => 1,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
        ]);
        $request = $this->createRequest($resident, RequestStatus::Assigned);
        ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
            'was_manual_override' => false,
        ]);

        $this->actingAs($personnelUser)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::EnRoute->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::EnRoute->value,
        ]);
    }

    public function test_personnel_cannot_update_another_responders_request(): void
    {
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $otherPersonnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $otherPersonnel = ResponsePersonnel::create([
            'user_id' => $otherPersonnelUser->id,
            'name' => 'Other Responder',
            'specialization' => 'general_assistance',
            'is_available' => true,
            'current_workload' => 1,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
        ]);
        $request = $this->createRequest($resident, RequestStatus::Assigned);
        ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $otherPersonnel->id,
            'was_manual_override' => false,
        ]);

        $this->actingAs($personnelUser)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::EnRoute->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Assigned->value,
        ]);
    }

    public function test_resolving_request_closes_assignment_and_releases_workload(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Assigned);
        $personnel = ResponsePersonnel::create([
            'name' => 'Test Responder',
            'specialization' => 'general_assistance',
            'is_available' => true,
            'current_workload' => 1,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
        ]);
        $assignment = ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
            'was_manual_override' => false,
        ]);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::Resolved->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('response_assignments', [
            'id' => $assignment->id,
        ]);
        $this->assertNotNull($assignment->fresh()->completed_at);

        $this->assertDatabaseHas('response_personnel', [
            'id' => $personnel->id,
            'current_workload' => 0,
        ]);
    }
}
