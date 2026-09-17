<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_resident_cannot_access_official_personnel_management(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);

        $this->actingAs($resident)
            ->get(route('personnel.index'))
            ->assertForbidden();
    }

    public function test_personnel_cannot_access_official_personnel_management(): void
    {
        $personnel = User::factory()->create(['role' => UserRole::Personnel]);

        $this->actingAs($personnel)
            ->get(route('personnel.index'))
            ->assertForbidden();
    }

    public function test_resident_cannot_access_manual_assignment(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);

        $this->actingAs($resident)
            ->get(route('personnel.index'))
            ->assertForbidden();
    }
}
