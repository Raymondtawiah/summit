<?php

namespace Database\Factories;

use App\Models\ScanPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanPoint>
 */
class ScanPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Main Entrance',
                'Bus Boarding',
                'Accommodation',
                'Meals',
                'Main Hall',
                'Workshop A',
                'Workshop B',
                'Closing Session',
            ]),
            'location' => fake()->optional()->streetAddress(),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
