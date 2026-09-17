<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\EmergencyRequest;
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
}
