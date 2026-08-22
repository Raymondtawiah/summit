<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->optional()->randomElement([
                'Samsung Galaxy S23',
                'iPhone 15',
                'Pixel 8',
                'OnePlus 12',
                'Xiaomi 14',
            ]),
            'device_identifier' => fake()->unique()->regexify('[A-Z0-9]{16}'),
            'device_token' => fake()->unique()->regexify('[A-Za-z0-9]{64}'),
            'staff_id' => User::factory(),
            'last_sync_at' => null,
            'dataset_version' => null,
            'data_invalidated' => false,
            'status' => 'active',
        ];
    }

    public function forStaff(User $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff->id,
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync_at' => now(),
        ]);
    }
}
