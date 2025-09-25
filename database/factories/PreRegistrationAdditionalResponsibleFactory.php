<?php

namespace Database\Factories;

use App\Models\PreRegistration;
use App\Models\PreRegistrationAdditionalResponsible;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreRegistrationAdditionalResponsibleFactory extends Factory
{
    protected $model = PreRegistrationAdditionalResponsible::class;

    public function definition(): array
    {
        return [
            'pre_registration_id' => PreRegistration::factory(),
            'name' => $this->faker->name(),
            'cpf' => $this->faker->numerify('###########'),
            'kinship' => $this->faker->randomElement(['pai', 'mãe', 'irmão']),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
