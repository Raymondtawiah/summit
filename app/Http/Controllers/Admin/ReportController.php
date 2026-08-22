<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\ScanPoint;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected ReportService $reportService,
    ) {}

    public function attendance(Request $request)
    {
        $report = $this->reportService->attendanceReport($request);

        $filterOptions = [
            'access_points' => \App\Models\ScanPoint::orderBy('name')->get(['id', 'name']),
            'staff' => \App\Models\User::staff()->orderBy('name')->get(['id', 'name']),
            'results' => AttendanceLog::select('result')->distinct()->pluck('result')->filter(),
            'scan_modes' => AttendanceLog::select('scan_mode')->distinct()->pluck('scan_mode')->filter(),
        ];

        return view('admin.reports.attendance', compact('report', 'filterOptions'));
    }

    public function participation()
    {
        $report = $this->reportService->participationReport();

        return view('admin.reports.participation', compact('report'));
    }

    public function units()
    {
        $report = $this->reportService->unitReport();

        return view('admin.reports.units', compact('report'));
    }

    public function stakes()
    {
        $report = $this->reportService->stakeReport();

        return view('admin.reports.stakes', compact('report'));
    }

    public function accessPoints()
    {
        $report = $this->reportService->accessPointReport();

        return view('admin.reports.access-points', compact('report'));
    }

    public function staff()
    {
        $report = $this->reportService->staffReport();

        return view('admin.reports.staff', compact('report'));
    }

public function devices()
        {
            $report = $this->reportService->deviceReport();

            return view('admin.reports.devices', compact('report'));
        }

        public function auditLogs(Request $request)
        {
            $report = $this->reportService->auditLogReport($request);

            $filterOptions = [
                'actions' => AuditLog::select('action')->distinct()->pluck('action')->filter(),
                'users' => User::orderBy('name')->get(['id', 'name']),
                'entity_types' => AuditLog::select('entity_type')->distinct()->pluck('entity_type')->filter(),
            ];

            return view('admin.reports.audit-logs', compact('report', 'filterOptions'));
        }

public function export(Request $request, string $type)
        {
            $allowed = ['attendance', 'participation', 'units', 'stakes', 'access-points', 'staff', 'devices', 'audit-logs'];
            if (!in_array($type, $allowed)) {
                abort(403);
            }

        $format = $request->input('format', 'csv');

        if ($format === 'csv') {
            return $this->exportCsv($type);
        }

        if ($format === 'excel') {
            return response()->json(['message' => 'Excel export is temporarily unavailable. Please use CSV.'], 501);
        }

        abort(403);
    }

    private function exportCsv(string $type)
    {
        $filename = 'summit-' . $type . '-report.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($type) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getHeaders($type));

            foreach ($this->getRows($type) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

private function getHeaders(string $type): array
        {
            return match ($type) {
                'attendance' => ['Participant', 'Registration Number', 'Unit', 'Stake/District', 'Access Point', 'Staff', 'Device', 'Date', 'Time', 'Mode', 'Result'],
                'participation' => ['Participant', 'Registration Number', 'Unit', 'Stake/District', 'Attended', 'Total', 'Percentage'],
                'units' => ['Unit', 'Registered', 'Scanned', 'Percentage'],
                'stakes' => ['Stake/District', 'Registered', 'Scanned', 'Percentage'],
                'access-points' => ['Access Point', 'Type', 'Granted', 'Duplicates', 'Denied', 'Total', 'Percentage'],
                'staff' => ['Staff', 'Access Point', 'Total Scans', 'Successful', 'Duplicates', 'Denied', 'Offline'],
                'devices' => ['Device', 'Staff', 'Access Point', 'Last Sync', 'Status'],
                'audit-logs' => ['ID', 'User', 'Action', 'Entity Type', 'Entity ID', 'Description', 'Old Values', 'New Values', 'IP Address', 'User Agent', 'Created At'],
                default => [],
            };
        }

private function getRows(string $type): \Illuminate\Support\Collection
        {
            return match ($type) {
                'attendance' => $this->reportService->attendanceReport(request())->get()->map(fn ($log) => [
                    $log->participant->full_name ?? '—',
                    $log->participant->registration_number ?? '—',
                    $log->participant->unit ?? '—',
                    $log->participant->stake_district ?? '—',
                    $log->scanPoint->name ?? '—',
                    $log->staff->name ?? '—',
                    $log->device->name ?? '—',
                    $log->scanned_at?->format('Y-m-d'),
                    $log->scanned_at?->format('H:i'),
                    $log->scan_mode,
                    $log->result,
                ]),
                'participation' => collect($this->reportService->participationReport())->map(fn ($row) => [
                    $row['name'], $row['registration_number'], $row['unit'], $row['stake_district'],
                    $row['attended'], $row['total'], $row['percentage'] . '%',
                ]),
                'units' => collect($this->reportService->unitReport())->map(fn ($row) => [
                    $row['unit'], $row['registered'], $row['scanned'], $row['percentage'] . '%',
                ]),
                'stakes' => collect($this->reportService->stakeReport())->map(fn ($row) => [
                    $row['stake_district'], $row['registered'], $row['scanned'], $row['percentage'] . '%',
                ]),
                'access-points' => collect($this->reportService->accessPointReport())->map(fn ($row) => [
                    $row['name'], $row['type'], $row['granted'], $row['duplicates'], $row['denied'], $row['total'], $row['percentage'] . '%',
                ]),
                'staff' => collect($this->reportService->staffReport())->map(fn ($row) => [
                    $row['name'], $row['access_point'], $row['total'], $row['successful'], $row['duplicates'], $row['denied'], $row['offline'],
                ]),
                'devices' => collect($this->reportService->deviceReport())->map(fn ($row) => [
                    $row['name'], $row['staff'], $row['access_point'], $row['last_sync'], $row['status'],
                ]),
                'audit-logs' => $this->reportService->auditLogReport(request())->get()->map(fn ($log) => [
                    $log->id,
                    $log->user->name ?? '—',
                    $log->action,
                    $log->entity_type,
                    $log->entity_id ?? '—',
                    $log->description,
                    $log->old_values ? json_encode($log->old_values) : '—',
                    $log->new_values ? json_encode($log->new_values) : '—',
                    $log->ip_address ?? '—',
                    $log->user_agent ?? '—',
                    $log->created_at?->format('Y-m-d H:i:s'),
                ]),
                default => collect(),
            };
        }
}
