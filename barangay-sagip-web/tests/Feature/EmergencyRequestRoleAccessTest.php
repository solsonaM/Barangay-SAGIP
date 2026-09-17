<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyRequestRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_access_request_form(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);

        $this->actingAs($resident)
            ->get(route('requests.create'))
            ->assertOk();
    }

    public function test_official_cannot_access_resident_request_form(): void
    {
        $official = User::factory()->create(['role' => UserRole::Official]);

        $this->actingAs($official)
            ->get(route('requests.create'))
            ->assertForbidden();
    }

    public function test_personnel_cannot_access_resident_request_form(): void
    {
        $personnel = User::factory()->create(['role' => UserRole::Personnel]);

        $this->actingAs($personnel)
            ->get(route('requests.create'))
            ->assertForbidden();
    }
}
