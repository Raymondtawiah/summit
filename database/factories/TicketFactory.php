<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        $year = now()->year;
        $sequence = fake()->unique()->numberBetween(1, 999999);

        return [
            'participant_id' => Participant::factory(),
            'ticket_number' => 'TKT-'.$year.'-'.str_pad($sequence, 6, '0', STR_PAD_LEFT),
            'qr_token' => bin2hex(random_bytes(32)),
            'status' => 'active',
            'generated_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }

    public function replaced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'replaced',
        ]);
    }
}
