<?php

namespace Database\Seeders;

use App\Models\PreAppointment;
use App\Models\PreRegistration;
use App\Models\PreRegistrationAdditionalResponsible;
use App\Models\PreRegistrationEmergencyContact;
use Illuminate\Database\Seeder;

class PreRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        PreRegistration::factory()
            ->count(10)
            ->has(PreRegistrationEmergencyContact::factory()->count(2), 'emergencyContacts')
            ->has(PreRegistrationAdditionalResponsible::factory()->count(1), 'additionalResponsibles')
            ->has(PreAppointment::factory()->count(1), 'appointment')
            ->create();
    }
}
