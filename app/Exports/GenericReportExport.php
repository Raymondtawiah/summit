<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class GenericReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected string $type,
        protected Request $request,
    ) {}

    public function collection()
    {
        $service = app(ReportService::class);

        return match ($this->type) {
            'attendance' => $service->attendanceReport($this->request)->get()->map(fn ($log) => [
                $log->participant->full_name ?? '—',
                $log->participant->registration_number ?? '—',
                $log->participant->unit ?? '—',
                $log->participant->stake_district ?? '—',
                $log->scanPoint->name ?? '—',
                $log->staff->name ?? '—',
                $log->device->name ?? '—',
                $log->scanned_at?->format('Y-m-d H:i:s'),
                $log->scan_mode,
                $log->result,
            ]),
            'participation' => collect($service->participationReport()),
            'units' => collect($service->unitReport()),
            'stakes' => collect($service->stakeReport()),
            'access-points' => collect($service->accessPointReport()),
            'staff' => collect($service->staffReport()),
            'devices' => collect($service->deviceReport()),
            default => collect(),
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'attendance' => ['Participant', 'Registration Number', 'Unit', 'Stake/District', 'Access Point', 'Staff', 'Device', 'Scanned At', 'Mode', 'Result'],
            'participation' => ['Participant', 'Registration Number', 'Unit', 'Stake/District', 'Attended', 'Total', 'Percentage'],
            'units' => ['Unit', 'Registered', 'Scanned', 'Percentage'],
            'stakes' => ['Stake/District', 'Registered', 'Scanned', 'Percentage'],
            'access-points' => ['Access Point', 'Type', 'Granted', 'Duplicates', 'Denied', 'Total', 'Percentage'],
            'staff' => ['Staff', 'Access Point', 'Total Scans', 'Successful', 'Duplicates', 'Denied', 'Offline'],
            'devices' => ['Device', 'Staff', 'Access Point', 'Last Sync', 'Status'],
            default => [],
        };
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
