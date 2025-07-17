<?php

namespace Database\Factories;

use App\Models\PreRegistration;
use App\Models\PreRegistrationLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreRegistrationFactory extends Factory
{
    protected $model = PreRegistration::class;

    public function definition(): array
    {
        return [
            'pre_registration_link_id' => PreRegistrationLink::factory(),
            'child_name' => $this->faker->name(),
            'child_birthdate' => $this->faker->date('Y-m-d', '-3 years'),
            'child_gender' => $this->faker->randomElement(['masculino', 'feminino']),
            'child_cpf' => $this->faker->numerify('###########'),
            'child_sus' => $this->faker->optional()->numerify('###########'),
            'child_nationality' => $this->faker->optional()->country(),
            'child_address' => $this->faker->address(),
            'child_residence_type' => $this->faker->optional()->word(),
            'child_phone' => $this->faker->optional()->phoneNumber(),
            'child_cellphone' => $this->faker->phoneNumber(),
            'child_school' => $this->faker->optional()->word(),
            'has_other_clinic' => $this->faker->boolean(),
            'other_clinic_info' => $this->faker->optional()->sentence(),
            'care_type' => $this->faker->randomElement(['particular', 'liminar', 'garantia', 'convenio']),
            'responsible_name' => $this->faker->name(),
            'responsible_kinship' => $this->faker->randomElement(['pai', 'mãe', 'tutor']),
            'responsible_birthdate' => $this->faker->optional()->date(),
            'responsible_nationality' => $this->faker->optional()->country(),
            'responsible_cpf' => $this->faker->numerify('###########'),
            'responsible_rg' => $this->faker->numerify('#########'),
            'responsible_profession' => $this->faker->optional()->jobTitle(),
            'responsible_phones' => $this->faker->phoneNumber(),
            'responsible_email' => $this->faker->safeEmail(),
            'responsible_address' => $this->faker->address(),
            'responsible_residence_type' => $this->faker->optional()->word(),
            'authorized_to_pick_up' => $this->faker->boolean(),
            'is_financial_responsible' => $this->faker->boolean(),
            'status' => $this->faker->randomElement(['aguardando', 'agendado', 'cancelado']),
        ];
    }
}

