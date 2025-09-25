<?php

namespace Database\Factories;

use App\Models\PreRegistrationLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PreRegistrationLinkFactory extends Factory
{
    protected $model = PreRegistrationLink::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['normal', 'anamnese']),
            'specialty' => $this->faker->randomElement(['Fisioterapia', 'Fonoaudiologia', 'Terapia Ocupacional']),
            'token' => Str::uuid(),
            'user_id' => User::factory(),
        ];
    }
}
