<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function dashboard()
    {
        $staff = Auth::user();
        $scanPoint = $staff->scanPoint;
        $isReady = false;
        $readyMessage = 'Scanner is not ready.';

        if ($staff->role === 'staff' && $staff->status === 'active') {
            if ($scanPoint && $scanPoint->status === 'active') {
                $isReady = true;
                $readyMessage = 'Scanner is ready.';
            } elseif (!$scanPoint) {
                $readyMessage = 'No scan point has been assigned to your account. Please contact the administrator.';
            } elseif ($scanPoint->status !== 'active') {
                $readyMessage = 'This scan point is currently inactive. Please contact the administrator.';
            }
        } elseif ($staff->status !== 'active') {
            $readyMessage = 'Your account is inactive. Please contact the administrator.';
        }

        $today = now()->toDateString();
        $todayScans = $staff->attendanceLogs()
            ->whereDate('scanned_at', $today)
            ->get();

        $stats = [
            'total' => $todayScans->count(),
            'successful' => $todayScans->where('result', 'access_granted')->count(),
            'denied' => $todayScans->whereIn('result', ['access_closed', 'access_not_open', 'scan_point_inactive'])->count(),
            'duplicates' => $todayScans->where('result', 'duplicate')->count(),
            'pending_sync' => $todayScans->where('sync_status', 'pending')->count(),
        ];

        $device = $staff->devices()->active()->first();

        return view('staff.dashboard', compact('staff', 'scanPoint', 'isReady', 'readyMessage', 'stats', 'device'));
    }

    public function scanner()
    {
        $staff = Auth::user();
        $scanPoint = $staff->scanPoint;
        $isReady = false;
        $readyMessage = 'Scanner is not ready.';

        if ($staff->role === 'staff' && $staff->status === 'active') {
            if ($scanPoint && $scanPoint->status === 'active') {
                $isReady = true;
                $readyMessage = 'Scanner is ready.';
            } elseif (!$scanPoint) {
                $readyMessage = 'No scan point has been assigned to your account. Please contact the administrator.';
            } elseif ($scanPoint->status !== 'active') {
                $readyMessage = 'This scan point is currently inactive. Please contact the administrator.';
            }
        } elseif ($staff->status !== 'active') {
            $readyMessage = 'Your account is inactive. Please contact the administrator.';
        }

        return view('staff.scanner', compact('staff', 'scanPoint', 'isReady', 'readyMessage'));
    }

    public function scans()
    {
        $staff = Auth::user();

        $scans = $staff->attendanceLogs()
            ->with(['participant', 'scanPoint', 'ticket'])
            ->latest('scanned_at')
            ->paginate(20);

        return view('staff.scans', compact('staff', 'scans'));
    }

    public function profile()
    {
        $staff = Auth::user();
        $device = $staff->devices()->active()->first();

        return view('staff.profile', compact('staff', 'device'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $staff = Auth::user();
        $staff->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }
}

