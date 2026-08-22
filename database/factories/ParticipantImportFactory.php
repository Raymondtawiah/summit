<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantImport>
 */
class ParticipantImportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'file_name' => fake()->randomElement(['participants_2026.xlsx', 'summit_list.xlsx', 'official_list.xlsx']),
            'uploaded_by' => User::factory(),
            'total_rows' => fake()->numberBetween(10, 500),
            'imported_count' => fake()->numberBetween(5, 490),
            'updated_count' => fake()->numberBetween(0, 10),
            'skipped_count' => fake()->numberBetween(0, 5),
            'duplicate_count' => fake()->numberBetween(0, 5),
            'error_count' => fake()->numberBetween(0, 5),
            'status' => fake()->randomElement(['processing', 'completed', 'completed_with_errors', 'failed']),
            'started_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ParticipantImport::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function withErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ParticipantImport::STATUS_COMPLETED_WITH_ERRORS,
            'error_count' => fake()->numberBetween(1, 10),
            'completed_at' => now(),
        ]);
    }
}
