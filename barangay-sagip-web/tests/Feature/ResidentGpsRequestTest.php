<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentGpsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_request_page_contains_live_device_geolocation_flow(): void
    {
        $resident = User::factory()->create(['role' => UserRole::Resident]);

        $this->actingAs($resident)
            ->get(route('requests.create'))
            ->assertOk()
            ->assertSee('navigator.geolocation.watchPosition', false)
            ->assertSee('enableHighAccuracy: true', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('https://barangay-sagip.test', false);
    }
}
