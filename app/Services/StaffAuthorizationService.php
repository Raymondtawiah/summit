<?php

namespace App\Services;

use App\Models\User;

class StaffAuthorizationService
{
    public function canStaffScan(User $staff): array
    {
        if ($staff->role !== 'staff') {
            return [
                'ready' => false,
                'reason' => 'not_staff',
                'message' => 'User is not a staff member.',
            ];
        }

        if ($staff->status !== 'active') {
            return [
                'ready' => false,
                'reason' => 'staff_inactive',
                'message' => 'Your account is inactive. Please contact the administrator.',
            ];
        }

        if (!$staff->scan_point_id) {
            return [
                'ready' => false,
                'reason' => 'no_scan_point',
                'message' => 'No access point has been assigned to your account. Please contact the administrator.',
            ];
        }

        return [
            'ready' => true,
            'reason' => 'READY',
            'message' => 'Scanner is ready.',
            'staff' => $staff,
            'scan_point' => $staff->scanPoint,
        ];
    }
}
