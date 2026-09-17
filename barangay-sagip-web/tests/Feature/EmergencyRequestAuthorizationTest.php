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

    public function test_resident_can_view_only_their_own_request(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $otherResident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $otherResident->id,
            'status' => RequestStatus::Validated,
        ]);

        $this->actingAs($resident)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }

    public function test_resident_can_view_their_own_request(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);

        $this->actingAs($resident)
            ->get(route('requests.show', $request))
            ->assertOk();
    }
}
