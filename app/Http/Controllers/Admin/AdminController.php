<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function dashboard(Request $request)
    {
        $filters = [
            'date' => $request->input('date'),
            'access_point_id' => $request->input('access_point_id'),
            'staff_id' => $request->input('staff_id'),
        ];

        $summary = $this->dashboardService->getSummary();
        $recentScans = $this->dashboardService->getRecentScans(20);
        $accessPointPerformance = $this->dashboardService->getAccessPointPerformance();
        $attendanceOverTime = $this->dashboardService->getAttendanceOverTime(7);

        return view('admin.dashboard', compact('summary', 'recentScans', 'accessPointPerformance', 'attendanceOverTime', 'filters'));
    }
}
