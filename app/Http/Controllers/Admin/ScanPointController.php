<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ScanPoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ScanPointController extends Controller
{
    public function index(Request $request)
    {
        $query = ScanPoint::query()->select('scan_points.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('scan_points.name', 'like', "%{$search}%")
                    ->orWhere('scan_points.location', 'like', "%{$search}%")
                    ->orWhere('scan_points.description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('scan_points.status', $status);
        }

        $allowedSorts = [
            'name' => 'scan_points.name',
            'location' => 'scan_points.location',
            'created_at' => 'scan_points.created_at',
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

        $scanPoints = $query->withCount('users')->paginate(20)->appends($request->query());

        return view('admin.scan-points.index', compact('scanPoints'));
    }

    public function create()
    {
        return view('admin.scan-points.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:scan_points,code'],
            'type' => ['required', 'in:transport,accommodation,entrance,meal,activity,session,other'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
            'requires_ticket' => ['boolean'],
            'duplicate_rule' => ['required', 'in:once_ever,once_per_day,once_per_session,multiple_allowed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['requires_ticket'] = $request->boolean('requires_ticket', true);

        $scanPoint = ScanPoint::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_SCAN_POINT_CREATED,
            'entity_type' => 'scan_point',
            'entity_id' => $scanPoint->id,
            'description' => 'Created access point: '.$scanPoint->name,
            'new_values' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.scan-points')->with('success', 'Access point created successfully.');
    }

    public function show(ScanPoint $scanPoint)
    {
        $scanPoint->load(['users', 'attendanceLogs.participant', 'attendanceLogs.staff']);

        $assignedStaff = $scanPoint->users()->orderBy('name')->get(['id', 'name', 'email', 'status', 'last_login_at']);

        $recentActivity = $scanPoint->attendanceLogs()
            ->with(['participant', 'staff'])
            ->latest('scanned_at')
            ->limit(20)
            ->get();

        return view('admin.scan-points.show', compact('scanPoint', 'assignedStaff', 'recentActivity'));
    }

    public function edit(ScanPoint $scanPoint)
    {
        return view('admin.scan-points.edit', compact('scanPoint'));
    }

    public function update(Request $request, ScanPoint $scanPoint)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('scan_points', 'code')->ignore($scanPoint->id)],
            'type' => ['required', 'in:transport,accommodation,entrance,meal,activity,session,other'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
            'requires_ticket' => ['boolean'],
            'duplicate_rule' => ['required', 'in:once_ever,once_per_day,once_per_session,multiple_allowed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['requires_ticket'] = $request->boolean('requires_ticket', true);

        $oldValues = $scanPoint->only(['name', 'code', 'type', 'location', 'status', 'duplicate_rule', 'start_time', 'end_time']);

        $scanPoint->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_SCAN_POINT_UPDATED,
            'entity_type' => 'scan_point',
            'entity_id' => $scanPoint->id,
            'description' => 'Updated access point: '.$scanPoint->name,
            'old_values' => $oldValues,
            'new_values' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.scan-points.show', $scanPoint)->with('success', 'Access point updated successfully.');
    }

    public function activate(Request $request, ScanPoint $scanPoint)
    {
        if ($scanPoint->status === 'active') {
            return back()->with('info', 'Scan point is already active.');
        }

        $scanPoint->update(['status' => 'active']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_SCAN_POINT_UPDATED,
            'entity_type' => 'scan_point',
            'entity_id' => $scanPoint->id,
            'description' => 'Activated scan point: '.$scanPoint->name,
            'old_values' => ['status' => 'inactive'],
            'new_values' => ['status' => 'active'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Scan point activated successfully.');
    }

    public function deactivate(Request $request, ScanPoint $scanPoint)
    {
        if ($scanPoint->status === 'inactive') {
            return back()->with('info', 'Scan point is already inactive.');
        }

        $scanPoint->update(['status' => 'inactive']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_SCAN_POINT_UPDATED,
            'entity_type' => 'scan_point',
            'entity_id' => $scanPoint->id,
            'description' => 'Deactivated scan point: '.$scanPoint->name,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'inactive'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Scan point deactivated successfully.');
    }
}
