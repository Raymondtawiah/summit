<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncBatch>
 */
class SyncBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'device_id' => Device::factory(),
            'staff_id' => User::factory(),
            'status' => 'pending',
            'records_count' => fake()->numberBetween(1, 50),
            'successful_count' => 0,
            'failed_count' => 0,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'records_count' => 10,
            'successful_count' => 10,
            'failed_count' => 0,
        ]);
    }

    public function partiallyFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partially_failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'records_count' => 10,
            'successful_count' => 8,
            'failed_count' => 2,
        ]);
    }
}
