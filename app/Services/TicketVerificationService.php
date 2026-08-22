<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Participant;

class TicketVerificationService
{
    public function verify(string $qrToken): array
    {
        $ticket = Ticket::where('qr_token', $qrToken)
            ->with('participant')
            ->first();

        if (!$ticket) {
            return [
                'valid' => false,
                'reason' => 'token_not_found',
                'message' => 'Ticket not found.',
                'ticket' => null,
                'participant' => null,
            ];
        }

        if ($ticket->status === 'revoked') {
            return [
                'valid' => false,
                'reason' => 'ticket_revoked',
                'message' => 'This ticket has been revoked.',
                'ticket' => $ticket,
                'participant' => $ticket->participant,
            ];
        }

        if ($ticket->status === 'replaced') {
            return [
                'valid' => false,
                'reason' => 'ticket_replaced',
                'message' => 'This ticket has been replaced by a newer ticket.',
                'ticket' => $ticket,
                'participant' => $ticket->participant,
            ];
        }

        if ($ticket->status !== 'active') {
            return [
                'valid' => false,
                'reason' => 'ticket_invalid',
                'message' => 'This ticket is not valid.',
                'ticket' => $ticket,
                'participant' => $ticket->participant,
            ];
        }

        if (!$ticket->participant || $ticket->participant->status !== 'active') {
            return [
                'valid' => false,
                'reason' => 'participant_inactive',
                'message' => 'The participant associated with this ticket is inactive.',
                'ticket' => $ticket,
                'participant' => $ticket->participant,
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
            'message' => 'Ticket is valid.',
            'ticket' => $ticket,
            'participant' => $ticket->participant,
        ];
    }
}
