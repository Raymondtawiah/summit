<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SynchronizationService
{
    public function getCurrentDatasetVersion(): int
    {
        return (int) DB::table('dataset_versions')->max('version');
    }

    public function getDatasetVersionTimestamp(): ?string
    {
        $row = DB::table('dataset_versions')->orderByDesc('version')->first();

        return $row?->created_at?->toIso8601String();
    }

    public function getParticipantsQuery()
    {
        return Participant::query()
            ->select([
                'id',
                'registration_number',
                'first_name',
                'last_name',
                'unit',
                'stake_district',
                'shirt_size',
                'status',
                'updated_at',
            ])
            ->active()
            ->orderBy('id');
    }

    public function getTicketsQuery()
    {
        return Ticket::query()
            ->select([
                'id',
                'participant_id',
                'ticket_number',
                'qr_token',
                'status',
                'updated_at',
            ])
            ->with('participant:id,registration_number,first_name,last_name,unit,stake_district,status')
            ->orderBy('id');
    }

    public function getScanPoints()
    {
        return \App\Models\ScanPoint::query()
            ->select(['id', 'name', 'code', 'type', 'location', 'description', 'status', 'requires_ticket', 'duplicate_rule', 'start_date', 'end_date', 'start_time', 'end_time', 'capacity'])
            ->orderBy('name')
            ->get()
            ->map(fn ($sp) => [
                'id' => $sp->id,
                'name' => $sp->name,
                'code' => $sp->code,
                'type' => $sp->type,
                'location' => $sp->location,
                'description' => $sp->description,
                'status' => $sp->status,
                'requires_ticket' => (bool) $sp->requires_ticket,
                'duplicate_rule' => $sp->duplicate_rule,
                'start_date' => $sp->start_date?->toDateString(),
                'end_date' => $sp->end_date?->toDateString(),
                'start_time' => $sp->start_time?->format('H:i'),
                'end_time' => $sp->end_time?->format('H:i'),
                'capacity' => $sp->capacity,
            ])
            ->values()
            ->all();
    }

    public function getChangesSinceVersion(int $sinceVersion): array
    {
        $participants = $this->getParticipantsQuery()
            ->where('updated_at', '>', $this->versionTimestamp($sinceVersion))
            ->get()
            ->map(fn ($p) => [
                'type' => 'participant',
                'id' => $p->id,
                'registration_number' => $p->registration_number,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'unit' => $p->unit,
                'stake_district' => $p->stake_district,
                'shirt_size' => $p->shirt_size,
                'status' => $p->status,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $tickets = $this->getTicketsQuery()
            ->where('updated_at', '>', $this->versionTimestamp($sinceVersion))
            ->get()
            ->map(fn ($t) => [
                'type' => 'ticket',
                'id' => $t->id,
                'participant_id' => $t->participant_id,
                'ticket_number' => $t->ticket_number,
                'qr_token' => $t->qr_token,
                'status' => $t->status,
                'updated_at' => $t->updated_at?->toIso8601String(),
                'participant' => $t->participant ? [
                    'registration_number' => $t->participant->registration_number,
                    'first_name' => $t->participant->first_name,
                    'last_name' => $t->participant->last_name,
                ] : null,
            ])
            ->values()
            ->all();

        return [
            'participants' => $participants,
            'tickets' => $tickets,
        ];
    }

    public function versionTimestamp(int $version): string
    {
        $row = DB::table('dataset_versions')->where('version', $version)->first();

        return $row?->created_at?->toDateTimeString() ?? '1970-01-01 00:00:00';
    }

    public function authorizeDevice(User $staff, ?string $deviceIdentifier, ?string $deviceToken): ?Device
    {
        if (!$deviceIdentifier && !$deviceToken) {
            return null;
        }

        $query = Device::query()
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->where('data_invalidated', false);

        if ($deviceToken) {
            $query->where('device_token', $deviceToken);
        } elseif ($deviceIdentifier) {
            $query->where('device_identifier', $deviceIdentifier);
        }

        return $query->first();
    }

    public function processSyncUpload(User $staff, ?Device $device, array $records): array
    {
        $results = [];
        $scanPoint = $staff->scanPoint;

        if (!$scanPoint || $scanPoint->status !== 'active') {
            return array_map(fn ($record) => [
                'local_uuid' => $record['local_uuid'] ?? null,
                'status' => 'rejected',
                'message' => 'Staff scan point is not active.',
            ], $records);
        }

        foreach ($records as $record) {
            $localUuid = $record['local_uuid'] ?? null;

            if (!$localUuid) {
                $results[] = [
                    'local_uuid' => null,
                    'status' => 'rejected',
                    'message' => 'Missing local_uuid.',
                ];
                continue;
            }

            $existing = AttendanceLog::where('local_uuid', $localUuid)->first();

            if ($existing) {
                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'duplicate',
                    'attendance_id' => $existing->id,
                    'message' => 'Record already synchronized.',
                ];
                continue;
            }

            $ticketId = $record['ticket_id'] ?? null;
            $participantId = $record['participant_id'] ?? null;
            $ticket = null;

            if ($ticketId) {
                $ticket = Ticket::find($ticketId);
            } elseif (!empty($record['qr_token'])) {
                $ticket = Ticket::where('qr_token', $record['qr_token'])->first();
            }

            if (!$ticket) {
                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'rejected',
                    'message' => 'Ticket not found.',
                ];
                continue;
            }

            if ($ticket->status !== 'active' || !$ticket->participant || $ticket->participant->status !== 'active') {
                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'rejected',
                    'message' => 'Ticket or participant is not active.',
                ];
                continue;
            }

            $duplicate = AttendanceLog::where('ticket_id', $ticket->id)
                ->where('scan_point_id', $scanPoint->id)
                ->whereNotNull('scanned_at')
                ->first();

            if ($duplicate) {
                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'conflict',
                    'message' => 'Ticket already scanned at this scan point.',
                    'attendance_id' => $duplicate->id,
                ];
                continue;
            }

            $clientScannedAt = !empty($record['scanned_at']) ? $record['scanned_at'] : now();

            try {
                $attendance = AttendanceLog::create([
                    'uuid' => Str::uuid(),
                    'local_uuid' => $localUuid,
                    'participant_id' => $ticket->participant_id,
                    'ticket_id' => $ticket->id,
                    'staff_id' => $staff->id,
                    'scan_point_id' => $scanPoint->id,
                    'device_id' => $device?->id,
                    'scanned_at' => $clientScannedAt,
                    'offline_created_at' => $clientScannedAt,
                    'scan_mode' => 'offline',
                    'sync_status' => 'synced',
                ]);

                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'synced',
                    'attendance_id' => $attendance->id,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'local_uuid' => $localUuid,
                    'status' => 'failed',
                    'message' => 'Server error: '.$e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function recordSyncBatch(Device $device, User $staff, int $recordsCount, string $status): void
    {
        \App\Models\SyncBatch::create([
            'uuid' => Str::uuid(),
            'device_id' => $device->id,
            'staff_id' => $staff->id,
            'status' => $status,
            'records_count' => $recordsCount,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $device->update([
            'last_sync_at' => now(),
            'dataset_version' => $this->getCurrentDatasetVersion(),
        ]);
    }
}
