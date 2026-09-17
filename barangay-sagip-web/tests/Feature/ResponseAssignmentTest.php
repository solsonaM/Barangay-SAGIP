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

class ResponseAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_cannot_access_manual_assignment_page(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);

        $this->actingAs($resident)
            ->get(route('requests.assign.edit', $request))
            ->assertForbidden();
    }

    public function test_unavailable_personnel_cannot_be_manually_assigned(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);
        $personnel = ResponsePersonnel::factory()->create(['is_available' => false]);

        $this->actingAs($official)
            ->post(route('requests.assign.store', $request), [
                'response_personnel_id' => $personnel->id,
            ])
            ->assertSessionHasErrors('response_personnel_id');

        $this->assertDatabaseMissing('response_assignments', [
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
        ]);
    }

    public function test_busy_personnel_cannot_be_manually_assigned(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);
        $otherRequest = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Assigned,
        ]);
        $personnel = ResponsePersonnel::factory()->create([
            'is_available' => true,
            'current_workload' => 1,
        ]);
        ResponseAssignment::factory()->create([
            'emergency_request_id' => $otherRequest->id,
            'response_personnel_id' => $personnel->id,
            'completed_at' => null,
        ]);

        $this->actingAs($official)
            ->post(route('requests.assign.store', $request), [
                'response_personnel_id' => $personnel->id,
            ])
            ->assertSessionHasErrors('response_personnel_id');

        $this->assertDatabaseMissing('response_assignments', [
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
        ]);
    }

    public function test_only_validated_or_assigned_requests_can_be_manually_assigned(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Submitted,
        ]);
        $personnel = ResponsePersonnel::factory()->create(['is_available' => true]);

        $this->actingAs($official)
            ->post(route('requests.assign.store', $request), [
                'response_personnel_id' => $personnel->id,
            ])
            ->assertSessionHasErrors('response_personnel_id');
    }

    public function test_manual_assignment_is_created_for_eligible_personnel(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);
        $personnel = ResponsePersonnel::factory()->create([
            'is_available' => true,
            'current_workload' => 0,
        ]);

        $this->actingAs($official)
            ->post(route('requests.assign.store', $request), [
                'response_personnel_id' => $personnel->id,
            ])
            ->assertRedirect(route('requests.show', $request));

        $this->assertDatabaseHas('response_assignments', [
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
            'was_manual_override' => true,
            'assigned_by' => $official->id,
        ]);

        $this->assertDatabaseHas('emergency_requests', [
            'id' => $request->id,
            'status' => RequestStatus::Assigned->value,
        ]);

        $this->assertDatabaseHas('response_personnel', [
            'id' => $personnel->id,
            'current_workload' => 1,
        ]);
    }
}
