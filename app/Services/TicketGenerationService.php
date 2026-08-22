<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketGenerationService
{
    public function generateTicket(Participant $participant, ?User $admin = null): Ticket
    {
        return DB::transaction(function () use ($participant, $admin) {
            $existingActive = $participant->activeTicket()->first();
            if ($existingActive) {
                return $existingActive;
            }

            $ticketNumber = $this->generateUniqueTicketNumber();
            $qrToken = $this->generateSecureQrToken();

            $ticket = Ticket::create([
                'participant_id' => $participant->id,
                'ticket_number' => $ticketNumber,
                'qr_token' => $qrToken,
                'status' => 'active',
                'generated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin?->id ?? Auth::id(),
                'action' => AuditLog::ACTION_TICKET_GENERATED,
                'entity_type' => 'ticket',
                'entity_id' => $ticket->id,
                'description' => 'Generated ticket for participant: '.$participant->full_name,
                'new_values' => [
                    'ticket_number' => $ticket->ticket_number,
                    'qr_token' => $qrToken,
                    'participant_id' => $participant->id,
                    'registration_number' => $participant->registration_number,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $ticket;
        });
    }

    public function generateBulkTickets(array $participantIds, ?User $admin = null): array
    {
        $results = [
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $participants = Participant::whereIn('id', $participantIds)
            ->where('status', 'active')
            ->get();

        foreach ($participants as $participant) {
            try {
                $existingActive = $participant->activeTicket()->exists();
                if ($existingActive) {
                    $results['skipped']++;
                    continue;
                }

                $this->generateTicket($participant, $admin);
                $results['generated']++;
            } catch (\Throwable $e) {
                $results['errors']++;
            }
        }

        return $results;
    }

    public function generateUniqueTicketNumber(): string
    {
        $year = now()->year;
        $prefix = 'TKT';

        do {
            $sequence = DB::table('tickets')
                ->whereYear('created_at', $year)
                ->count() + 1;

            $ticketNumber = $prefix.'-'.$year.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);

            $exists = DB::table('tickets')->where('ticket_number', $ticketNumber)->exists();
        } while ($exists);

        return $ticketNumber;
    }

    public function generateSecureQrToken(): string
    {
        do {
            $token = 'LDS-SUMMITPASS:'.bin2hex(random_bytes(32));
            $exists = DB::table('tickets')->where('qr_token', $token)->exists();
        } while ($exists);

        return $token;
    }
}
