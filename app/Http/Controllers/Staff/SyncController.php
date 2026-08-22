<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Services\SynchronizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function __construct(
        protected SynchronizationService $syncService,
    ) {}

    public function index(Request $request)
    {
        $staff = Auth::user();
        $device = $this->resolveDevice($request, $staff);

        $currentVersion = $this->syncService->getCurrentDatasetVersion();
        $deviceVersion = $device?->dataset_version;

        $stats = [
            'online' => $request->hasHeader('X-Device-Token') || $request->filled('device_identifier'),
            'last_sync_at' => $device?->last_sync_at,
            'dataset_version' => $currentVersion,
            'device_version' => $deviceVersion,
            'update_available' => $device ? $currentVersion > $deviceVersion : true,
            'data_invalidated' => $device?->data_invalidated ?? true,
        ];

        $today = now()->toDateString();
        $queuedScans = $staff->attendanceLogs()
            ->whereDate('scanned_at', $today)
            ->where('sync_status', 'pending')
            ->count();

        $syncedScans = $staff->attendanceLogs()
            ->whereDate('scanned_at', $today)
            ->where('sync_status', 'synced')
            ->count();

        $failedScans = $staff->attendanceLogs()
            ->whereDate('scanned_at', $today)
            ->where('sync_status', 'failed')
            ->count();

        return view('staff.sync', compact('staff', 'device', 'stats', 'queuedScans', 'syncedScans', 'failedScans'));
    }

    public function status(Request $request)
    {
        $staff = Auth::user();
        $device = $this->resolveDevice($request, $staff);

        $currentVersion = $this->syncService->getCurrentDatasetVersion();
        $deviceVersion = $device?->dataset_version;

        return response()->json([
            'dataset_version' => $currentVersion,
            'last_sync_at' => $device?->last_sync_at?->toIso8601String(),
            'device_version' => $deviceVersion,
            'update_available' => $device ? $currentVersion > $deviceVersion : true,
            'data_invalidated' => $device?->data_invalidated ?? true,
            'device_uuid' => $device?->uuid,
        ]);
    }

    public function download(Request $request)
    {
        $staff = Auth::user();
        $device = $this->resolveDevice($request, $staff);

        if (!$device) {
            return response()->json(['message' => 'Unauthorized device.'], 403);
        }

        if ($device->data_invalidated) {
            return response()->json(['message' => 'Device data has been invalidated. Please re-authenticate.'], 403);
        }

        $currentVersion = $this->syncService->getCurrentDatasetVersion();
        $deviceVersion = (int) ($device->dataset_version ?? 0);

        if ($deviceVersion > 0 && $currentVersion <= $deviceVersion) {
            return response()->json([
                'message' => 'Dataset already up to date.',
                'dataset_version' => $currentVersion,
                'participants' => [],
                'tickets' => [],
                'scan_points' => $this->syncService->getScanPoints(),
            ]);
        }

        $limit = 500;
        $page = max(1, (int) $request->input('page', 1));
        $sinceVersion = $page === 1 ? 0 : $deviceVersion;

        $participants = \App\Models\Participant::query()
            ->select(['id', 'registration_number', 'first_name', 'last_name', 'unit', 'stake_district', 'shirt_size', 'status', 'updated_at'])
            ->active()
            ->when($sinceVersion > 0, fn ($q) => $q->where('updated_at', '>', $this->syncService->versionTimestamp($sinceVersion)))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $tickets = \App\Models\Ticket::query()
            ->select(['id', 'participant_id', 'ticket_number', 'qr_token', 'status', 'updated_at'])
            ->whereHas('participant', fn ($q) => $q->active())
            ->when($sinceVersion > 0, fn ($q) => $q->where('updated_at', '>', $this->syncService->versionTimestamp($sinceVersion)))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $scanPoints = $this->syncService->getScanPoints();

        $participantsCount = \App\Models\Participant::active()->count();
        $ticketsCount = \App\Models\Ticket::whereHas('participant', fn ($q) => $q->active())->count();

        return response()->json([
            'dataset_version' => $currentVersion,
            'page' => $page,
            'participants' => $participants,
            'tickets' => $tickets,
            'access_points' => $this->syncService->getScanPoints(),
            'meta' => [
                'participants_count' => $participantsCount,
                'tickets_count' => $ticketsCount,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $staff = Auth::user();
        $device = $this->resolveDevice($request, $staff);

        if (!$device) {
            return response()->json(['message' => 'Unauthorized device.'], 403);
        }

        if ($device->data_invalidated) {
            return response()->json(['message' => 'Device data has been invalidated.'], 403);
        }

        $request->validate([
            'records' => ['required', 'array', 'max:500'],
            'records.*.local_uuid' => ['required', 'string'],
            'records.*.ticket_id' => ['nullable', 'integer'],
            'records.*.participant_id' => ['nullable', 'integer'],
            'records.*.qr_token' => ['nullable', 'string'],
            'records.*.scanned_at' => ['nullable', 'string'],
            'records.*.scan_mode' => ['nullable', 'string', 'in:offline'],
        ]);

        $results = $this->syncService->processSyncUpload($staff, $device, $request->input('records', []));

        $successCount = collect($results)->where('status', 'synced')->count();
        $this->syncService->recordSyncBatch($device, $staff, count($results), $successCount === count($results) ? 'completed' : 'partially_failed');

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    private function resolveDevice(Request $request, User $staff): ?Device
    {
        $deviceToken = $request->header('X-Device-Token');
        $deviceIdentifier = $request->input('device_identifier');

        if (!$deviceToken && !$deviceIdentifier) {
            return null;
        }

        return Device::query()
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->when($deviceToken, fn ($q) => $q->where('device_token', $deviceToken))
            ->when(!$deviceToken && $deviceIdentifier, fn ($q) => $q->where('device_identifier', $deviceIdentifier))
            ->first();
    }
}
