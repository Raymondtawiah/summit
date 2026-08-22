<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function processScan(User $staff, string $qrToken, ?Device $device = null): array
    {
        $scanPoint = $staff->scanPoint;

        if (!$scanPoint) {
            return [
                'success' => false,
                'result' => 'no_scan_point',
                'message' => 'No access point has been assigned to your account.',
                'participant' => null,
                'ticket' => null,
                'access' => null,
                'scan' => null,
            ];
        }

        $accessControl = new AccessControlService(new AttendanceRuleService());
        $result = $accessControl->evaluateAccess($staff, $qrToken, $scanPoint);

        if ($result['success'] && !empty($result['attendance'])) {
            $this->logAudit('scan_valid', $staff, $result['attendance']->participant_id, $result['attendance']->ticket_id, ['attendance_id' => $result['attendance']->id]);
        } elseif (!$result['success']) {
            $participantId = $result['participant']?->id;
            $ticketId = $result['ticket']?->id;
            $action = match ($result['result']) {
                'invalid' => 'scan_invalid',
                'revoked' => 'scan_revoked',
                'replaced' => 'scan_replaced',
                'duplicate' => 'scan_duplicate',
                'inactive_participant' => 'scan_inactive_participant',
                'access_closed', 'access_inactive' => 'scan_invalid',
                default => 'scan_invalid',
            };
            $this->logAudit($action, $staff, $participantId, $ticketId);
        }

        return $result;
    }

    protected function logAudit(string $action, User $staff, ?int $participantId, ?int $ticketId, array $extra = []): void
    {
        \App\Models\AuditLog::create([
            'user_id' => $staff->id,
            'action' => $action,
            'entity_type' => 'attendance',
            'entity_id' => $ticketId,
            'description' => "Scan attempt: {$action} by {$staff->name}",
            'old_values' => null,
            'new_values' => array_merge(['staff_id' => $staff->id, 'participant_id' => $participantId, 'ticket_id' => $ticketId], $extra),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}

