<?php

namespace App\Services;

use App\Models\Ticket;

class TicketPdfService
{
    public function buildPdfResponse(Ticket $ticket)
    {
        $html = view('admin.tickets.pdf', compact('ticket'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="ticket-'.$ticket->ticket_number.'.html"',
        ]);
    }
}
