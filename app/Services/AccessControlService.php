<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    public function __construct(
        protected AttendanceRuleService $ruleService,
    ) {}

    public function evaluateAccess(User $staff, ?string $qrToken, ?ScanPoint $accessPoint): array
    {
        if (!$accessPoint) {
            return [
                'success' => false,
                'result' => 'access_inactive',
                'message' => 'No access point has been assigned to your account.',
                'participant' => null,
                'ticket' => null,
                'access' => null,
                'attendance' => null,
            ];
        }

        if ($accessPoint->status !== 'active') {
            return [
                'success' => false,
                'result' => 'access_inactive',
                'message' => 'This access point is currently disabled.',
                'participant' => null,
                'ticket' => null,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        $event = $accessPoint->event;
        if ($event && $event->status === 'completed') {
            return [
                'success' => false,
                'result' => 'access_closed',
                'message' => 'This event has ended.',
                'participant' => null,
                'ticket' => null,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        if (!$qrToken) {
            return [
                'success' => false,
                'result' => 'invalid',
                'message' => 'No QR token provided.',
                'participant' => null,
                'ticket' => null,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        $ticket = Ticket::where('qr_token', $qrToken)
            ->with('participant')
            ->first();

        if (!$ticket) {
            return [
                'success' => false,
                'result' => 'invalid',
                'message' => 'This QR code is not recognized.',
                'participant' => null,
                'ticket' => null,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        if ($ticket->status === 'revoked') {
            return [
                'success' => false,
                'result' => 'revoked',
                'message' => 'This ticket has been revoked.',
                'participant' => $ticket->participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        if ($ticket->status === 'replaced') {
            return [
                'success' => false,
                'result' => 'replaced',
                'message' => 'This ticket has been replaced. Please use the latest ticket.',
                'participant' => $ticket->participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        if ($ticket->status !== 'active') {
            return [
                'success' => false,
                'result' => 'invalid',
                'message' => 'This ticket is not valid.',
                'participant' => $ticket->participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        $participant = $ticket->participant;
        if (!$participant || $participant->status !== 'active') {
            return [
                'success' => false,
                'result' => 'inactive_participant',
                'message' => 'This participant is not currently eligible.',
                'participant' => $participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        $timeResult = $this->evaluateTimeWindow($accessPoint);
        if ($timeResult['blocked']) {
            return [
                'success' => false,
                'result' => $timeResult['result'],
                'message' => $timeResult['message'],
                'participant' => $participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => null,
            ];
        }

        $duplicate = $this->findDuplicate($ticket->id, $accessPoint);
        if ($duplicate) {
            return [
                'success' => false,
                'result' => 'duplicate',
                'message' => 'Participant has already been recorded for this access point.',
                'participant' => $participant,
                'ticket' => $ticket,
                'access' => $this->formatAccess($accessPoint),
                'attendance' => $duplicate,
            ];
        }

        $attendance = DB::transaction(function () use ($staff, $ticket, $participant, $accessPoint) {
            return AttendanceLog::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'participant_id' => $participant->id,
                'ticket_id' => $ticket->id,
                'staff_id' => $staff->id,
                'scan_point_id' => $accessPoint->id,
                'scanned_at' => now(),
                'scan_mode' => 'online',
                'sync_status' => 'synced',
                'result' => 'valid',
                'access_type' => $accessPoint->type,
                'attendance_rule' => $accessPoint->duplicate_rule,
                'server_received_at' => now(),
                'sync_attempts' => 0,
            ]);
        });

        return [
            'success' => true,
            'result' => 'access_granted',
            'message' => 'Access granted.',
            'participant' => $participant,
            'ticket' => $ticket,
            'access' => $this->formatAccess($accessPoint),
            'attendance' => $attendance,
        ];
    }

    public function evaluateTimeWindow(ScanPoint $accessPoint): array
    {
        $now = now();

        if ($accessPoint->start_date && $now->lt($accessPoint->start_date)) {
            return [
                'blocked' => true,
                'result' => 'access_closed',
                'message' => 'This access point is not yet open.',
            ];
        }

        if ($accessPoint->end_date && $now->gt($accessPoint->end_date)) {
            return [
                'blocked' => true,
                'result' => 'access_closed',
                'message' => 'This access point has closed.',
            ];
        }

        if ($accessPoint->start_time && $now->format('H:i') < $accessPoint->start_time->format('H:i')) {
            return [
                'blocked' => true,
                'result' => 'access_closed',
                'message' => 'Access opens at '.$accessPoint->start_time->format('H:i'),
            ];
        }

        if ($accessPoint->end_time && $now->format('H:i') > $accessPoint->end_time->format('H:i')) {
            return [
                'blocked' => true,
                'result' => 'access_closed',
                'message' => 'Access closed at '.$accessPoint->end_time->format('H:i'),
            ];
        }

        return ['blocked' => false];
    }

    public function findDuplicate(int $ticketId, ScanPoint $accessPoint): ?AttendanceLog
    {
        $rule = $accessPoint->duplicate_rule;
        $query = AttendanceLog::query()
            ->where('ticket_id', $ticketId)
            ->where('scan_point_id', $accessPoint->id)
            ->whereNotNull('scanned_at');

        switch ($rule) {
            case 'once_ever':
                return $query->first();
            case 'once_per_day':
                return $query->whereDate('scanned_at', now()->toDateString())->first();
            case 'once_per_session':
                return $query->whereDate('scanned_at', now()->toDateString())->first();
            case 'multiple_allowed':
            default:
                return null;
        }
    }

    private function formatAccess(ScanPoint $accessPoint): array
    {
        return [
            'id' => $accessPoint->id,
            'name' => $accessPoint->name,
            'type' => $accessPoint->type,
            'rule' => $accessPoint->duplicate_rule,
            'start_time' => $accessPoint->start_time?->format('H:i'),
            'end_time' => $accessPoint->end_time?->format('H:i'),
        ];
    }
}
