<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = AttendanceLog::query();
        if ($from) {
            $query->where('scanned_at', '>=', $from);
        }
        if ($to) {
            $query->where('scanned_at', '<=', $to);
        }

        $totalParticipants = Participant::where('status', 'active')->count();
        $uniqueScanned = $query->distinct('participant_id')->count('participant_id');
        $participationRate = $totalParticipants > 0 ? round(($uniqueScanned / $totalParticipants) * 100, 1) : 0;

        $totalTickets = Ticket::count();
        $activeTickets = Ticket::where('status', 'active')->count();
        $revokedTickets = Ticket::where('status', 'revoked')->count();
        $replacedTickets = Ticket::where('status', 'replaced')->count();

        $totalScans = $query->count();
        $todayScans = AttendanceLog::whereDate('scanned_at', now()->toDateString())->count();
        $pendingSync = AttendanceLog::where('sync_status', 'pending')->count();
        $failedSync = AttendanceLog::where('sync_status', 'failed')->count();
        $conflictSync = AttendanceLog::where('sync_status', 'conflict')->count();

        return [
            'participants' => [
                'total' => $totalParticipants,
                'scanned' => $uniqueScanned,
                'not_scanned' => max(0, $totalParticipants - $uniqueScanned),
                'participation_rate' => $participationRate,
            ],
            'tickets' => [
                'total' => $totalTickets,
                'active' => $activeTickets,
                'revoked' => $revokedTickets,
                'replaced' => $replacedTickets,
            ],
            'attendance' => [
                'total_scans' => $totalScans,
                'today_scans' => $todayScans,
                'pending_sync' => $pendingSync,
                'failed_sync' => $failedSync,
                'conflict_sync' => $conflictSync,
            ],
            'devices' => [
                'total' => Device::count(),
                'active' => Device::where('status', 'active')->count(),
                'invalidated' => Device::where('data_invalidated', true)->count(),
            ],
        ];
    }

    public function getRecentScans(int $limit = 20): array
    {
        return AttendanceLog::query()
            ->with(['participant', 'ticket', 'staff', 'scanPoint'])
            ->latest('scanned_at')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'participant' => $log->participant->full_name ?? '—',
                'registration_number' => $log->participant->registration_number ?? '—',
                'access_point' => $log->scanPoint->name ?? '—',
                'staff' => $log->staff->name ?? '—',
                'time' => $log->scanned_at?->format('H:i'),
                'mode' => $log->scan_mode,
                'result' => $log->result,
            ])
            ->all();
    }

    public function getAccessPointPerformance(): array
    {
        return ScanPoint::query()
            ->withCount(['attendanceLogs as granted_count' => fn ($q) => $q->where('result', 'access_granted')])
            ->withCount(['attendanceLogs as duplicate_count' => fn ($q) => $q->where('result', 'duplicate')])
            ->withCount(['attendanceLogs as denied_count' => fn ($q) => $q->whereNotIn('result', ['access_granted', 'duplicate'])])
            ->orderBy('name')
            ->get()
            ->map(fn ($sp) => [
                'name' => $sp->name,
                'type' => $sp->type,
                'granted' => $sp->granted_count,
                'duplicates' => $sp->duplicate_count,
                'denied' => $sp->denied_count,
                'total' => $sp->attendance_logs_count,
            ])
            ->all();
    }

    public function getAttendanceOverTime(int $days = 7): array
    {
        $data = AttendanceLog::query()
            ->select(DB::raw('DATE(scanned_at) as date'), DB::raw('COUNT(*) as scans'))
            ->where('scanned_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(fn ($row) => [
            'date' => Carbon::parse($row->date)->format('M d'),
            'scans' => (int) $row->scans,
        ])->all();
    }

    public function getParticipationByUnit(): array
    {
        return Participant::query()
            ->select('unit', DB::raw('COUNT(*) as total'))
            ->groupBy('unit')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'unit' => $row->unit ?? 'Unknown',
                'registered' => $row->total,
            ])
            ->all();
    }

    public function getParticipationByStake(): array
    {
        return Participant::query()
            ->select('stake_district', DB::raw('COUNT(*) as total'))
            ->groupBy('stake_district')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'stake_district' => $row->stake_district ?? 'Unknown',
                'registered' => $row->total,
            ])
            ->all();
    }

    public function getStaffPerformance(): array
    {
        return User::staff()
            ->withCount(['attendanceLogs as total_scans'])
            ->withCount(['attendanceLogs as successful_scans' => fn ($q) => $q->where('result', 'access_granted')])
            ->withCount(['attendanceLogs as duplicate_scans' => fn ($q) => $q->where('result', 'duplicate')])
            ->withCount(['attendanceLogs as denied_scans' => fn ($q) => $q->whereNotIn('result', ['access_granted', 'duplicate'])])
            ->orderByDesc('total_scans')
            ->get()
            ->map(fn ($staff) => [
                'name' => $staff->name,
                'access_point' => $staff->scanPoint->name ?? '—',
                'total_scans' => $staff->total_scans,
                'successful' => $staff->successful_scans,
                'duplicates' => $staff->duplicate_scans,
                'denied' => $staff->denied_scans,
            ])
            ->all();
    }

    public function getDeviceStatus(): array
    {
        return Device::query()
            ->with(['staff', 'syncBatches'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($device) => [
                'name' => $device->name ?? $device->device_identifier,
                'staff' => $device->staff->name ?? '—',
                'access_point' => $device->staff->scanPoint->name ?? '—',
                'last_sync' => $device->last_sync_at?->diffForHumans(),
                'status' => $device->status,
                'data_invalidated' => $device->data_invalidated,
            ])
            ->all();
    }
}
