<?php

namespace Database\Factories;

use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'participant_id' => \App\Models\Participant::factory(),
            'ticket_id' => Ticket::factory(),
            'staff_id' => User::factory(),
            'scan_point_id' => ScanPoint::factory(),
            'device_id' => null,
            'scanned_at' => now(),
            'scan_mode' => 'online',
            'sync_status' => 'synced',
            'offline_created_at' => null,
        ];
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_mode' => 'offline',
            'sync_status' => 'pending',
            'offline_created_at' => now(),
        ]);
    }

    public function pendingSync(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'pending',
        ]);
    }

    public function forParticipant(\App\Models\Participant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => $participant->id,
        ]);
    }

    public function forTicket(Ticket $ticket): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $ticket->id,
            'participant_id' => $ticket->participant_id,
        ]);
    }

    public function forStaff(User $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff->id,
        ]);
    }

    public function forScanPoint(ScanPoint $scanPoint): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_point_id' => $scanPoint->id,
        ]);
    }
}
