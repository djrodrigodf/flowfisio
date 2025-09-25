<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Rodrigo Caixeta',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);

        $this->call(PreRegistrationSeeder::class);
        $this->call(RolePermissionSeeder::class);

        $this->call([
            SpecialtySeeder::class,
            TreatmentTypeSeeder::class,
            LocationSeeder::class,
            RoomSeeder::class,
            // M1
            InsuranceSeeder::class,
            TreatmentSeeder::class,
            TreatmentPriceSeeder::class,
            TreatmentPayoutSeeder::class,
            // M2
            ProfessionalSeeder::class,
            ProfessionalRoomSeeder::class,
            ProfessionalScheduleSeeder::class,
            ProfessionalTimeoffSeeder::class,
            ScheduleBlockSeeder::class,
            // M3
            PatientSeeder::class,
        ]);

    }
}
