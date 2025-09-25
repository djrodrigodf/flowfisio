<?php

namespace Database\Factories;

use App\Models\PreAppointment;
use App\Models\PreRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreAppointmentFactory extends Factory
{
    protected $model = PreAppointment::class;

    public function definition(): array
    {
        return [
            'pre_registration_id' => PreRegistration::factory(),
            'date' => $this->faker->date(),
            'time' => $this->faker->time(),
            'convenio' => $this->faker->optional()->company(),
            'guide_number' => $this->faker->optional()->bothify('###-####'),
            'procedure' => $this->faker->randomElement(['Fisioterapia', 'Fonoaudiologia']),
            'professional' => $this->faker->name(),
            'room' => $this->faker->optional()->numerify('Sala #'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
