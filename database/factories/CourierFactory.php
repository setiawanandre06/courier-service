<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Courier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'vehicle_type' => $this->faker->randomElement(['Motorcycle', 'Car', 'Van', 'Truck']),
            'vehicle_plate' => strtoupper($this->faker->unique()->bothify('?? #### ??')),
            'level' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'joined_at' => now(),
        ];
    }
}
