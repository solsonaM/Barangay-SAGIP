<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\EmergencyRequest;
use App\Models\ResponseAssignment;
use App\Models\ResponsePersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident, RequestStatus::Validated);

        $this->actingAs($official)
            ->patch(route('requests.updateStatus', $request), [
                'status' => RequestStatus::EnRoute->value,
                'note' => 'Responder is on the way.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::EnRoute->value,
        ]);

        $this->assertDatabaseHas('request_status_logs', [
            'emergency_request_id' => $request->id,
            'status' => RequestStatus::EnRoute->value,
            'changed_by' => $official->id,
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
