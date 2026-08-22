<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateParticipantRequest;
use App\Models\AuditLog;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = Participant::query()
            ->with(['activeTicket'])
            ->select('participants.*');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('participants.registration_number', 'like', "%{$search}%")
                    ->orWhere('participants.first_name', 'like', "%{$search}%")
                    ->orWhere('participants.last_name', 'like', "%{$search}%")
                    ->orWhere('participants.contact', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(participants.first_name, ' ', participants.last_name) like ?", ["%{$search}%"]);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('participants.status', $status);
        }

        if ($stakeDistrict = $request->input('stake_district')) {
            $query->where('participants.stake_district', $stakeDistrict);
        }

        if ($unit = $request->input('unit')) {
            $query->where('participants.unit', $unit);
        }

        if ($shirtSize = $request->input('shirt_size')) {
            $query->where('participants.shirt_size', $shirtSize);
        }

        if ($ticketStatus = $request->input('ticket_status')) {
            if ($ticketStatus === 'no_ticket') {
                $query->whereDoesntHave('tickets', function ($q) {
                    $q->where('status', '!=', 'replaced')->orWhereNull('status');
                });
            } else {
                $query->whereHas('tickets', function ($q) use ($ticketStatus) {
                    $q->where('status', $ticketStatus);
                });
            }
        }

        $allowedSorts = [
            'registration_number' => 'participants.registration_number',
            'first_name' => 'participants.first_name',
            'last_name' => 'participants.last_name',
            'stake_district' => 'participants.stake_district',
            'unit' => 'participants.unit',
            'created_at' => 'participants.created_at',
        ];

        $sort = $request->input('sort', 'registration_number');
        $direction = $request->input('direction', 'asc');

        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'registration_number';
        }

        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query->orderBy($allowedSorts[$sort], $direction);

        $participants = $query->paginate(20)->appends($request->query());

        $filterOptions = [
            'stake_districts' => Participant::select('stake_district')
                ->distinct()
                ->whereNotNull('stake_district')
                ->orderBy('stake_district')
                ->pluck('stake_district'),
            'units' => Participant::select('unit')
                ->distinct()
                ->whereNotNull('unit')
                ->orderBy('unit')
                ->pluck('unit'),
            'shirt_sizes' => Participant::select('shirt_size')
                ->distinct()
                ->whereNotNull('shirt_size')
                ->orderBy('shirt_size')
                ->pluck('shirt_size'),
        ];

        return view('admin.participants.index', compact('participants', 'filterOptions'));
    }

    public function show(Participant $participant)
    {
        $participant->load(['tickets', 'attendanceLogs.staff', 'attendanceLogs.scanPoint', 'activeTicket']);

        $attendanceStats = [
            'total_scans' => $participant->attendanceLogs()->count(),
            'latest_scan' => $participant->attendanceLogs()->latest('scanned_at')->first(),
            'scan_points_visited' => $participant->attendanceLogs()
                ->select('scan_point_id', DB::raw('count(*) as total'))
                ->groupBy('scan_point_id')
                ->get(),
        ];

        return view('admin.participants.show', compact('participant', 'attendanceStats'));
    }

    public function edit(Participant $participant)
    {
        return view('admin.participants.edit', compact('participant'));
    }

    public function update(UpdateParticipantRequest $request, Participant $participant)
    {
        $oldValues = $participant->only(['first_name', 'last_name', 'contact', 'age', 'unit', 'stake_district', 'shirt_size', 'status']);
        $newValues = $request->validated();

        $participant->update($newValues);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_PARTICIPANT_UPDATED,
            'entity_type' => 'participant',
            'entity_id' => $participant->id,
            'description' => 'Updated participant: '.$participant->full_name,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.participants.show', $participant)->with('success', 'Participant updated successfully.');
    }
}
