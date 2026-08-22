<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\SyncBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        $query = Device::query()->with(['staff', 'syncBatches']);

        if ($search = $request->input('search')) {
            $query->whereHas('staff', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $devices = $query->orderByDesc('updated_at')->paginate(20)->appends($request->query());

        $stats = [
            'total_devices' => Device::count(),
            'active_devices' => Device::where('status', 'active')->count(),
            'invalidated_devices' => Device::where('data_invalidated', true)->count(),
            'total_sync_batches' => SyncBatch::count(),
            'pending_batches' => SyncBatch::where('status', 'pending')->count(),
            'failed_batches' => SyncBatch::where('status', 'failed')->count(),
            'queued_attendance' => AttendanceLog::where('sync_status', 'pending')->count(),
            'synced_attendance' => AttendanceLog::where('sync_status', 'synced')->count(),
            'failed_attendance' => AttendanceLog::where('sync_status', 'failed')->count(),
            'conflict_attendance' => AttendanceLog::where('sync_status', 'conflict')->count(),
        ];

        return view('admin.synchronization.index', compact('devices', 'stats'));
    }
}
