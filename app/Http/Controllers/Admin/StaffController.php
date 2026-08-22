<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ScanPoint;
use App\Models\User;
use App\Services\StaffAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function __construct(
        protected StaffAuthorizationService $staffAuthorizationService,
    ) {}

    public function index(Request $request)
    {
        $query = User::query()
            ->with(['scanPoint'])
            ->staff()
            ->select('users.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('users.status', $status);
        }

        if ($scanPointId = $request->input('scan_point_id')) {
            $query->where('users.scan_point_id', $scanPointId);
        }

        $allowedSorts = [
            'name' => 'users.name',
            'email' => 'users.email',
            'created_at' => 'users.created_at',
            'last_login_at' => 'users.last_login_at',
        ];

        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'name';
        }

        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query->orderBy($allowedSorts[$sort], $direction);

        $staff = $query->paginate(20)->appends($request->query());

        $filterOptions = [
            'scan_points' => ScanPoint::orderBy('name')->get(['id', 'name']),
        ];

        return view('admin.staff.index', compact('staff', 'filterOptions'));
    }

    public function create()
    {
        $scanPoints = ScanPoint::orderBy('name')->get(['id', 'name', 'location', 'status']);

        return view('admin.staff.create', compact('scanPoints'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'scan_point_id' => ['nullable', 'exists:scan_points,id'],
        ]);

        $staff = new User();
        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->password = Hash::make($validated['password']);
        $staff->role = 'staff';
        $staff->status = $validated['status'];
        $staff->scan_point_id = $validated['scan_point_id'];
        $staff->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_STAFF_CREATED,
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => 'Created staff account: '.$staff->name,
            'new_values' => [
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
                'status' => $staff->status,
                'scan_point_id' => $staff->scan_point_id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.staff')->with('success', 'Staff account created successfully.');
    }

    public function show(User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        $staff->load(['scanPoint', 'attendanceLogs.scanPoint', 'attendanceLogs.participant']);

        $attendanceStats = [
            'total_scans' => $staff->attendanceLogs()->count(),
            'today_scans' => $staff->attendanceLogs()->whereDate('scanned_at', now()->toDateString())->count(),
            'online_scans' => $staff->attendanceLogs()->where('scan_mode', 'online')->count(),
            'offline_scans' => $staff->attendanceLogs()->where('scan_mode', 'offline')->count(),
            'last_scan' => $staff->attendanceLogs()->latest('scanned_at')->first(),
        ];

        return view('admin.staff.show', compact('staff', 'attendanceStats'));
    }

    public function edit(User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        $scanPoints = ScanPoint::orderBy('name')->get(['id', 'name', 'location', 'status']);

        return view('admin.staff.edit', compact('staff', 'scanPoints'));
    }

    public function update(Request $request, User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$staff->id],
            'status' => ['required', 'in:active,inactive'],
            'scan_point_id' => ['nullable', 'exists:scan_points,id'],
        ]);

        $oldValues = $staff->only(['name', 'email', 'status', 'scan_point_id']);
        $newValues = $validated;

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->status = $validated['status'];
        $staff->scan_point_id = $validated['scan_point_id'];
        $staff->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_STAFF_UPDATED,
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => 'Updated staff account: '.$staff->name,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.staff.show', $staff)->with('success', 'Staff account updated successfully.');
    }

    public function deactivate(Request $request, User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        if ($staff->status === 'inactive') {
            return back()->with('info', 'Staff account is already inactive.');
        }

        $staff->update(['status' => 'inactive']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_STAFF_DEACTIVATED,
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => 'Deactivated staff account: '.$staff->name,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'inactive'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Staff account deactivated successfully.');
    }

    public function activate(Request $request, User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        if ($staff->status === 'active') {
            return back()->with('info', 'Staff account is already active.');
        }

        $staff->update(['status' => 'active']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_STAFF_UPDATED,
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => 'Activated staff account: '.$staff->name,
            'old_values' => ['status' => 'inactive'],
            'new_values' => ['status' => 'active'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Staff account activated successfully.');
    }

    public function resetPassword(Request $request, User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff->update([
            'password' => Hash::make($request->input('password')),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'staff_password_reset',
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => 'Reset password for staff account: '.$staff->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Password reset successfully. Please provide the new password to the staff member.');
    }

    public function assignScanPoint(Request $request, User $staff)
    {
        if ($staff->role !== 'staff') {
            abort(404);
        }

        $validated = $request->validate([
            'scan_point_id' => ['nullable', 'exists:scan_points,id'],
        ]);

        $oldScanPointId = $staff->scan_point_id;

        $staff->update(['scan_point_id' => $validated['scan_point_id']]);

        $action = $oldScanPointId ? 'staff_scan_point_changed' : 'staff_scan_point_assigned';
        $description = $oldScanPointId
            ? 'Changed scan point for '.$staff->name
            : 'Assigned scan point to '.$staff->name;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $staff->id,
            'description' => $description,
            'old_values' => ['scan_point_id' => $oldScanPointId],
            'new_values' => ['scan_point_id' => $validated['scan_point_id']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Scan point assignment updated successfully.');
    }
}
