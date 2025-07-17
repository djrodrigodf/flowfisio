<?php

namespace Database\Seeders;


use App\Models\PreRegistrationAdditionalResponsible;
use Illuminate\Database\Seeder;
use App\Models\PreRegistration;
use App\Models\PreRegistrationEmergencyContact;

use App\Models\PreAppointment;

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
