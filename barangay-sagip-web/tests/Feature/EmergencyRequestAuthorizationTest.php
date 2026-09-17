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

class EmergencyRequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createRequest(User $resident): EmergencyRequest
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
            'status' => RequestStatus::Validated,
        ]);
    }

    private function createPersonnel(User $user): ResponsePersonnel
    {
        return ResponsePersonnel::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'specialization' => 'General Response',
            'availability_status' => 'available',
            'current_workload' => 0,
            'latitude' => 13.5925,
            'longitude' => 124.2049,
        ]);
    }

    private function assignRequest(EmergencyRequest $request, ResponsePersonnel $personnel): void
    {
        ResponseAssignment::create([
            'emergency_request_id' => $request->id,
            'response_personnel_id' => $personnel->id,
            'assigned_by' => $personnel->user_id,
            'assignment_type' => 'manual',
            'assigned_at' => now(),
        ]);
    }

    public function test_resident_cannot_view_another_residents_request(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $otherResident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($otherResident);

        $this->actingAs($resident)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }

    public function test_resident_can_view_own_request(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = $this->createRequest($resident);

        $this->actingAs($resident)
            ->get(route('requests.show', $request))
            ->assertOk();
    }

    public function test_personnel_can_view_request_assigned_to_them(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $personnel = $this->createPersonnel($personnelUser);
        $request = $this->createRequest($resident);
        $this->assignRequest($request, $personnel);

        $this->actingAs($personnelUser)
            ->get(route('requests.show', $request))
            ->assertOk();
    }

    public function test_personnel_cannot_view_request_assigned_to_another_responder(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $otherPersonnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $personnel = $this->createPersonnel($personnelUser);
        $otherPersonnel = $this->createPersonnel($otherPersonnelUser);
        $request = $this->createRequest($resident);
        $this->assignRequest($request, $otherPersonnel);

        $this->actingAs($personnelUser)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }

    public function test_personnel_cannot_view_unassigned_request(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $this->createPersonnel($personnelUser);
        $request = $this->createRequest($resident);

        $this->actingAs($personnelUser)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }
}
