<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ResponsePersonnel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds one demo account per role plus a handful of response personnel,
     * so the group can log in and exercise every feature immediately after
     * `php artisan migrate --seed` without registering accounts by hand.
     *
     * Demo logins (password for all: "password"):
     *   official@sagip.test   — barangay official
     *   personnel@sagip.test  — response personnel
     *   resident@sagip.test   — resident
     */
    public function run(): void
    {
        $official = User::create([
            'name' => 'Barangay Captain',
            'email' => 'official@sagip.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Official,
            'phone_number' => '09170000001',
        ]);

        $personnelUser = User::create([
            'name' => 'Tanod Juan Cruz',
            'email' => 'personnel@sagip.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Personnel,
            'phone_number' => '09170000002',
        ]);

        $resident = User::create([
            'name' => 'Maria Santos',
            'email' => 'resident@sagip.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Resident,
            'phone_number' => '09170000003',
        ]);

        $resident->residentProfile()->create([
            'full_name' => 'Maria Santos',
            'address' => 'Purok 2, Barangay Calatagan Tibang',
            'purok_sitio' => 'Purok 2',
            'household_members_count' => 4,
            'emergency_contact_name' => 'Jose Santos',
            'emergency_contact_number' => '09170000004',
            'home_latitude' => 13.5925,
            'home_longitude' => 124.2049,
        ]);

        // Link the seeded personnel user to a response_personnel record.
        ResponsePersonnel::create([
            'user_id' => $personnelUser->id,
            'name' => 'Tanod Juan Cruz',
            'specialization' => 'medical',
            'phone_number' => '09170000002',
            'latitude' => 13.5930,
            'longitude' => 124.2055,
            'is_available' => true,
            'current_workload' => 0,
            'last_location_update' => now(),
        ]);

        // A few more unlinked personnel across specializations, spread around
        // the actual Barangay Calatagan Tibang area (Virac, Catanduanes), so
        // the response-assignment scoring has real, geographically sensible
        // candidates to rank.
        $more = [
            ['name' => 'Tanod Pedro Reyes', 'specialization' => 'fire', 'latitude' => 13.5940, 'longitude' => 124.2100],
            ['name' => 'Tanod Ana Lopez', 'specialization' => 'peace_order', 'latitude' => 13.5900, 'longitude' => 124.2020],
            ['name' => 'Tanod Rico Bautista', 'specialization' => 'disaster', 'latitude' => 13.5960, 'longitude' => 124.2080],
            ['name' => 'Tanod Liza Fernandez', 'specialization' => 'general_assistance', 'latitude' => 13.5890, 'longitude' => 124.2060],
            ['name' => 'Tanod Mark Villanueva', 'specialization' => 'medical', 'latitude' => 13.5873, 'longitude' => 124.2059],
        ];

        foreach ($more as $p) {
            ResponsePersonnel::create($p + [
                'phone_number' => '0917' . rand(1000000, 9999999),
                'is_available' => true,
                'current_workload' => 0,
                'last_location_update' => now(),
            ]);
        }
    }
}
