<?php

namespace Database\Factories;

use App\Models\PreRegistration;
use App\Models\PreRegistrationEmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreRegistrationEmergencyContactFactory extends Factory
{
    protected $model = PreRegistrationEmergencyContact::class;

    public function definition(): array
    {
        return [
            'pre_registration_id' => PreRegistration::factory(),
            'name' => $this->faker->name(),
            'kinship' => $this->faker->randomElement(['tio', 'avó', 'vizinho']),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}

