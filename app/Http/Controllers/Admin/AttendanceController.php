<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\ScanPoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLog::query()
            ->with(['participant', 'ticket', 'staff', 'scanPoint'])
            ->select('attendance_logs.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('participant', function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                })
                ->orWhereHas('ticket', function ($q3) use ($search) {
                    $q3->where('ticket_number', 'like', "%{$search}%");
                })
                ->orWhereHas('staff', function ($q4) use ($search) {
                    $q4->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($scanPointId = $request->input('scan_point_id')) {
            $query->where('attendance_logs.scan_point_id', $scanPointId);
        }

        if ($staffId = $request->input('staff_id')) {
            $query->where('attendance_logs.staff_id', $staffId);
        }

        if ($scanMode = $request->input('scan_mode')) {
            $query->where('attendance_logs.scan_mode', $scanMode);
        }

        if ($result = $request->input('result')) {
            $query->where('attendance_logs.result', $result);
        }

        if ($accessType = $request->input('access_type')) {
            $query->whereHas('scanPoint', function ($q) use ($accessType) {
                $q->where('type', $accessType);
            });
        }

        if ($date = $request->input('date')) {
            $query->whereDate('attendance_logs.scanned_at', $date);
        }

        $stats = [
            'total_scans' => AttendanceLog::count(),
            'today_scans' => AttendanceLog::whereDate('scanned_at', now()->toDateString())->count(),
            'successful_scans' => AttendanceLog::where('scan_mode', 'online')->where('sync_status', 'synced')->count(),
            'rejected_scans' => AttendanceLog::whereNotNull('deleted_at')->count(),
        ];

        $attendanceLogs = $query->orderByDesc('attendance_logs.scanned_at')->paginate(20)->appends($request->query());

        $filterOptions = [
            'scan_points' => ScanPoint::orderBy('name')->get(['id', 'name']),
            'staff' => User::staff()->orderBy('name')->get(['id', 'name']),
            'access_types' => ScanPoint::select('type')->distinct()->pluck('type'),
            'results' => AttendanceLog::select('result')->distinct()->pluck('result')->filter(),
        ];

        return view('admin.attendance.index', compact('attendanceLogs', 'stats', 'filterOptions'));
    }
}
