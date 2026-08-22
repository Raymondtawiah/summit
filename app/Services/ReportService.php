<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function attendanceReport(Request $request)
    {
        $query = AttendanceLog::query()
            ->with(['participant.unit', 'participant.stake_district', 'ticket', 'staff', 'scanPoint', 'device'])
            ->select('attendance_logs.*');

        if ($date = $request->input('date')) {
            $query->whereDate('scanned_at', $date);
        } elseif ($dateRange = $request->input('date_range')) {
            $parts = explode(' to ', $dateRange);
            $query->whereDate('scanned_at', '>=', $parts[0] ?? now()->subDays(7));
            if (isset($parts[1])) {
                $query->whereDate('scanned_at', '<=', $parts[1]);
            }
        } else {
            $query->where('scanned_at', '>=', now()->subDays(7));
        }

        if ($accessPointId = $request->input('access_point_id')) {
            $query->where('scan_point_id', $accessPointId);
        }

        if ($staffId = $request->input('staff_id')) {
            $query->where('staff_id', $staffId);
        }

        if ($result = $request->input('result')) {
            $query->where('result', $result);
        }

        if ($scanMode = $request->input('scan_mode')) {
            $query->where('scan_mode', $scanMode);
        }

        return $query->orderByDesc('scanned_at')->paginate(50);
    }

    public function participationReport(): array
    {
        return Participant::query()
            ->withCount(['tickets as total_tickets'])
            ->withCount(['attendanceLogs as attended_count' => fn ($q) => $q->where('result', 'access_granted')])
            ->orderByDesc('attended_count')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->full_name,
                'registration_number' => $p->registration_number,
                'unit' => $p->unit,
                'stake_district' => $p->stake_district,
                'attended' => $p->attended_count,
                'total' => $p->total_tickets,
                'percentage' => $p->total_tickets > 0 ? round(($p->attended_count / $p->total_tickets) * 100, 1) : 0,
            ])
            ->all();
    }

    public function unitReport(): array
    {
        $results = Participant::query()
            ->select('unit', DB::raw('COUNT(*) as total'))
            ->groupBy('unit')
            ->get()
            ->map(fn ($p) => [
                'unit' => $p->unit ?? 'Unknown',
                'registered' => $p->total,
                'scanned' => 0,
                'percentage' => 0,
            ])
            ->values()
            ->all();

        $scannedByUnit = AttendanceLog::query()
            ->whereHas('participant', fn ($q) => $q->whereNotNull('unit'))
            ->whereIn('result', ['access_granted'])
            ->with('participant:id,unit')
            ->get()
            ->groupBy(fn ($log) => $log->participant->unit)
            ->map(fn ($logs) => $logs->unique('participant_id')->count());

        foreach ($results as &$row) {
            $scanned = $scannedByUnit->get($row['unit'], 0);
            $row['scanned'] = $scanned;
            $row['percentage'] = $row['registered'] > 0 ? round(($scanned / $row['registered']) * 100, 1) : 0;
        }

        usort($results, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        return $results;
    }

    public function stakeReport(): array
    {
        $results = Participant::query()
            ->select('stake_district', DB::raw('COUNT(*) as total'))
            ->groupBy('stake_district')
            ->get()
            ->map(fn ($p) => [
                'stake_district' => $p->stake_district ?? 'Unknown',
                'registered' => $p->total,
                'scanned' => 0,
                'percentage' => 0,
            ])
            ->values()
            ->all();

        $scannedByStake = AttendanceLog::query()
            ->whereHas('participant', fn ($q) => $q->whereNotNull('stake_district'))
            ->whereIn('result', ['access_granted'])
            ->with('participant:id,stake_district')
            ->get()
            ->groupBy(fn ($log) => $log->participant->stake_district)
            ->map(fn ($logs) => $logs->unique('participant_id')->count());

        foreach ($results as &$row) {
            $scanned = $scannedByStake->get($row['stake_district'], 0);
            $row['scanned'] = $scanned;
            $row['percentage'] = $row['registered'] > 0 ? round(($scanned / $row['registered']) * 100, 1) : 0;
        }

        usort($results, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        return $results;
    }

    public function accessPointReport(): array
    {
        return ScanPoint::query()
            ->withCount(['attendanceLogs as granted' => fn ($q) => $q->where('result', 'access_granted')])
            ->withCount(['attendanceLogs as duplicates' => fn ($q) => $q->where('result', 'duplicate')])
            ->withCount(['attendanceLogs as denied' => fn ($q) => $q->whereNotIn('result', ['access_granted', 'duplicate'])])
            ->orderBy('name')
            ->get()
            ->map(fn ($sp) => [
                'name' => $sp->name,
                'type' => $sp->type,
                'granted' => $sp->granted,
                'duplicates' => $sp->duplicates,
                'denied' => $sp->denied,
                'total' => $sp->attendance_logs_count,
                'percentage' => $sp->attendance_logs_count > 0 ? round(($sp->granted / $sp->attendance_logs_count) * 100, 1) : 0,
            ])
            ->all();
    }

    public function staffReport(): array
    {
        return User::staff()
            ->withCount(['attendanceLogs as total'])
            ->withCount(['attendanceLogs as successful' => fn ($q) => $q->where('result', 'access_granted')])
            ->withCount(['attendanceLogs as duplicates' => fn ($q) => $q->where('result', 'duplicate')])
            ->withCount(['attendanceLogs as denied' => fn ($q) => $q->whereNotIn('result', ['access_granted', 'duplicate'])])
            ->withCount(['attendanceLogs as offline' => fn ($q) => $q->where('scan_mode', 'offline')])
            ->orderByDesc('total')
            ->get()
            ->map(fn ($staff) => [
                'name' => $staff->name,
                'access_point' => $staff->scanPoint->name ?? '—',
                'total' => $staff->total,
                'successful' => $staff->successful,
                'duplicates' => $staff->duplicates,
                'denied' => $staff->denied,
                'offline' => $staff->offline,
            ])
            ->all();
    }

    public function deviceReport(): array
    {
        return Device::query()
            ->with(['staff', 'staff.scanPoint'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($device) => [
                'name' => $device->name ?? $device->device_identifier,
                'staff' => $device->staff->name ?? '—',
                'access_point' => $device->staff->scanPoint->name ?? '—',
                'last_sync' => $device->last_sync_at?->diffForHumans() ?? 'Never',
                'status' => $device->status,
                'data_invalidated' => $device->data_invalidated,
            ])
            ->all();
    }

    public function auditLogReport(Request $request)
    {
        $query = AuditLog::query()
            ->with(['user'])
            ->select('audit_logs.*');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($entityType = $request->input('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        } elseif ($dateRange = $request->input('date_range')) {
            $parts = explode(' to ', $dateRange);
            $query->whereDate('created_at', '>=', $parts[0] ?? now()->subDays(7));
            if (isset($parts[1])) {
                $query->whereDate('created_at', '<=', $parts[1]);
            }
        } else {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        return $query->orderByDesc('created_at')->paginate(50);
    }
}
