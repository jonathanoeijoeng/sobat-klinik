<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'satusehat_id' => 'P0' . $this->faker->unique()->numerify('#########'),
            'nik' => $this->faker->unique()->numerify('################'),
            'name' => strtoupper($this->faker->name()),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'), // Minimal 18 tahun
            'phone_number' => '628' . $this->faker->numerify('#########'),
            'address' => $this->faker->address(),
            'last_sync_at' => now(),
        ];
    }
}
