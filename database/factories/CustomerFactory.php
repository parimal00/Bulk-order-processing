<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CUST-####'),
            'name' => fake()->company(),
            'tier' => fake()->randomElement(['standard', 'silver', 'gold', 'platinum']),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->numerify('+1##########'),
            'is_active' => fake()->boolean(90),
        ];
    }
}
