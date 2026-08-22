<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StaffAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected StaffAuthorizationService $staffAuthorizationService,
    ) {}

    public function scan(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $staff = Auth::user();

        $authorization = $this->staffAuthorizationService->canStaffScan($staff);

        if (!$authorization['ready']) {
            return response()->json([
                'success' => false,
                'result' => 'unauthorized',
                'message' => $authorization['message'],
                'participant' => null,
                'ticket' => null,
                'scan' => null,
            ], 403);
        }

        $device = null;
        if ($request->filled('device_uuid')) {
            $device = Device::where('uuid', $request->input('device_uuid'))
                ->where('staff_id', $staff->id)
                ->first();
        }

        $result = $this->attendanceService->processScan($staff, $request->input('token'), $device);

        return response()->json($result);
    }

    public function todayScans(Request $request)
    {
        $staff = Auth::user();

        $authorization = $this->staffAuthorizationService->canStaffScan($staff);

        if (!$authorization['ready']) {
            return response()->json(['scans' => [], 'count' => 0]);
        }

        $scans = $staff->attendanceLogs()
            ->whereDate('scanned_at', now()->toDateString())
            ->with(['participant', 'scanPoint', 'ticket'])
            ->latest('scanned_at')
            ->limit(10)
            ->get();

        return response()->json([
            'scans' => $scans,
            'count' => $scans->count(),
        ]);
    }
}
