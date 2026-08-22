<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                \App\Models\AuditLog::ACTION_PARTICIPANT_IMPORTED,
                \App\Models\AuditLog::ACTION_TICKET_GENERATED,
                \App\Models\AuditLog::ACTION_TICKET_REVOKED,
                \App\Models\AuditLog::ACTION_STAFF_CREATED,
                \App\Models\AuditLog::ACTION_SCAN_POINT_CREATED,
            ]),
            'entity_type' => fake()->randomElement(['participant', 'ticket', 'staff', 'scan_point']),
            'entity_id' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => fake()->optional()->ipv4(),
            'user_agent' => fake()->optional()->userAgent(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withValues(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);
    }
}
