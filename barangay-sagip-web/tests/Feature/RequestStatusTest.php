<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\EmergencyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_can_update_request_status(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);
        $resident = User::factory()->create(['role' => UserRole::Resident]);
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);

        $this->actingAs($official)
            ->patch(route('requests.status.update', $request), [
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
        $request = EmergencyRequest::factory()->create([
            'resident_id' => $resident->id,
            'status' => RequestStatus::Validated,
        ]);

        $this->actingAs($resident)
            ->patch(route('requests.status.update', $request), [
                'status' => RequestStatus::Cancelled->value,
            ])
            ->assertForbidden();
    }
}
