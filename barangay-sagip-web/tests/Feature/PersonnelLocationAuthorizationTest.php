<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ResponsePersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelLocationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createPersonnel(User $user, float $latitude, float $longitude): ResponsePersonnel
    {
        return ResponsePersonnel::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'specialization' => 'general_assistance',
            'is_available' => true,
            'current_workload' => 0,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function test_personnel_can_update_only_their_own_location(): void
    {
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);
        $personnel = $this->createPersonnel($personnelUser, 13.5925, 124.2049);

        $this->actingAs($personnelUser)
            ->post(route('personnel.updateLocation'), [
                'latitude' => 13.6000,
                'longitude' => 124.2100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('response_personnel', [
            'id' => $personnel->id,
            'latitude' => 13.6000000,
            'longitude' => 124.2100000,
        ]);
    }

    public function test_personnel_location_update_requires_a_linked_personnel_record(): void
    {
        $personnelUser = User::factory()->create(['role' => UserRole::Personnel]);

        $this->actingAs($personnelUser)
            ->post(route('personnel.updateLocation'), [
                'latitude' => 13.6000,
                'longitude' => 124.2100,
            ])
            ->assertNotFound();
    }

    public function test_non_personnel_cannot_update_personnel_location(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);

        $this->actingAs($resident)
            ->post(route('personnel.updateLocation'), [
                'latitude' => 13.6000,
                'longitude' => 124.2100,
            ])
            ->assertForbidden();
    }
}
