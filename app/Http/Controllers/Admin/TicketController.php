<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Ticket;
use App\Services\TicketGenerationService;
use App\Services\TicketPdfService;
use App\Services\TicketVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function __construct(
        protected TicketGenerationService $ticketGenerationService,
        protected TicketVerificationService $ticketVerificationService,
        protected TicketPdfService $ticketPdfService,
    ) {}

    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['participant'])
            ->select('tickets.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tickets.ticket_number', 'like', "%{$search}%")
                    ->orWhere('tickets.qr_token', 'like', "%{$search}%")
                    ->orWhereHas('participant', function ($q2) use ($search) {
                        $q2->where('registration_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('tickets.status', $status);
        }

        if ($stakeDistrict = $request->input('stake_district')) {
            $query->whereHas('participant', function ($q) use ($stakeDistrict) {
                $q->where('stake_district', $stakeDistrict);
            });
        }

        if ($unit = $request->input('unit')) {
            $query->whereHas('participant', function ($q) use ($unit) {
                $q->where('unit', $unit);
            });
        }

        $stats = [
            'total_participants' => Participant::where('status', 'active')->count(),
            'participants_with_tickets' => Participant::whereHas('tickets', function ($q) {
                $q->where('status', 'active');
            })->count(),
            'participants_without_tickets' => Participant::where('status', 'active')
                ->whereDoesntHave('tickets', function ($q) {
                    $q->where('status', 'active');
                })
                ->count(),
            'active_tickets' => Ticket::where('status', 'active')->count(),
            'revoked_tickets' => Ticket::where('status', 'revoked')->count(),
            'replaced_tickets' => Ticket::where('status', 'replaced')->count(),
        ];

        $tickets = $query->orderByDesc('tickets.created_at')->paginate(20)->appends($request->query());

        $filterOptions = [
            'stake_districts' => Participant::select('stake_district')
                ->distinct()
                ->whereNotNull('stake_district')
                ->orderBy('stake_district')
                ->pluck('stake_district'),
            'units' => Participant::select('unit')
                ->distinct()
                ->whereNotNull('unit')
                ->orderBy('unit')
                ->pluck('unit'),
        ];

        return view('admin.tickets.index', compact('tickets', 'stats', 'filterOptions'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('participant');

        return view('admin.tickets.show', compact('ticket'));
    }

    public function print(Ticket $ticket)
    {
        $ticket->load('participant');

        $ticket->update(['printed_at' => now()]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_TICKET_PRINTED,
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
            'description' => 'Printed ticket: '.$ticket->ticket_number,
            'new_values' => [
                'ticket_number' => $ticket->ticket_number,
                'printed_at' => $ticket->printed_at,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return view('admin.tickets.print', compact('ticket'));
    }

    public function pdf(Ticket $ticket)
    {
        return $this->ticketPdfService->buildPdfResponse($ticket);
    }

    public function generate(Participant $participant)
    {
        $existingActive = $participant->activeTicket()->first();

        if ($existingActive) {
            return redirect()->route('admin.tickets.show', $existingActive)
                ->with('info', 'Active ticket already exists for this participant.');
        }

        $ticket = $this->ticketGenerationService->generateTicket($participant, Auth::user());

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket generated successfully.');
    }

    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:participants,id',
        ]);

        $results = $this->ticketGenerationService->generateBulkTickets($request->input('participant_ids'), Auth::user());

        return back()->with('success', "Tickets generated: {$results['generated']}, Skipped: {$results['skipped']}, Errors: {$results['errors']}");
    }

    public function generateMissing(Request $request)
    {
        $participantIds = Participant::where('status', 'active')
            ->whereDoesntHave('tickets', function ($q) {
                $q->where('status', 'active');
            })
            ->pluck('id')
            ->toArray();

        if (empty($participantIds)) {
            return back()->with('info', 'All active participants already have active tickets.');
        }

        $results = $this->ticketGenerationService->generateBulkTickets($participantIds, Auth::user());

        return back()->with('success', "Tickets generated: {$results['generated']}, Skipped: {$results['skipped']}, Errors: {$results['errors']}");
    }

    public function revoke(Request $request, Ticket $ticket)
    {
        if ($ticket->status !== 'active') {
            return back()->with('error', 'Only active tickets can be revoked.');
        }

        $ticket->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_TICKET_REVOKED,
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
            'description' => 'Revoked ticket: '.$ticket->ticket_number,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'revoked', 'revoked_at' => $ticket->revoked_at],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Ticket revoked successfully.');
    }

    public function replace(Request $request, Ticket $ticket)
    {
        if ($ticket->status !== 'active') {
            return back()->with('error', 'Only active tickets can be replaced.');
        }

        $newTicket = DB::transaction(function () use ($ticket) {
            $ticket->update([
                'status' => 'replaced',
                'revoked_at' => now(),
            ]);

            $newTicketNumber = $this->ticketGenerationService->generateUniqueTicketNumber();
            $newQrToken = $this->ticketGenerationService->generateSecureQrToken();

            $newTicket = Ticket::create([
                'participant_id' => $ticket->participant_id,
                'ticket_number' => $newTicketNumber,
                'qr_token' => $newQrToken,
                'status' => 'active',
                'generated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => AuditLog::ACTION_TICKET_REPLACED,
                'entity_type' => 'ticket',
                'entity_id' => $newTicket->id,
                'description' => 'Replaced ticket '.$ticket->ticket_number.' with '.$newTicket->ticket_number,
                'old_values' => [
                    'old_ticket_id' => $ticket->id,
                    'old_ticket_number' => $ticket->ticket_number,
                    'old_qr_token' => $ticket->qr_token,
                ],
                'new_values' => [
                    'new_ticket_id' => $newTicket->id,
                    'new_ticket_number' => $newTicket->ticket_number,
                    'new_qr_token' => $newQrToken,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $newTicket;
        });

        return redirect()->route('admin.tickets.show', $newTicket)
            ->with('success', 'Ticket replaced successfully. Old ticket is now marked as replaced.');
    }
}
