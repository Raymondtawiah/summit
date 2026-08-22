<?php

namespace Database\Factories;

use App\Models\ScanPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        $year = now()->year;
        $sequence = fake()->unique()->numberBetween(1, 999999);

        return [
            'registration_number' => 'SUM-'.$year.'-'.str_pad($sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'contact' => fake()->optional()->phoneNumber(),
            'age' => fake()->optional()->numberBetween(12, 90),
            'unit' => fake()->optional()->randomElement(['Ward 1', 'Ward 2', 'Ward 3', 'Branch A', 'Branch B']),
            'stake_district' => fake()->optional()->randomElement(['Stake A', 'Stake B', 'District C', 'District D']),
            'shirt_size' => fake()->optional()->randomElement(['S', 'M', 'L', 'XL', '2XL']),
            'assigned_scan_point_id' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function withoutScanPoint(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_scan_point_id' => null,
        ]);
    }

    public function withScanPoint($scanPointId): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_scan_point_id' => $scanPointId,
        ]);
    }
}
