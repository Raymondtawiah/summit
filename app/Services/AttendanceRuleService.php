<?php

namespace App\Services;

use App\Models\ScanPoint;

class AttendanceRuleService
{
    public function isAllowed(string $rule, ?string $date = null, ?string $session = null): bool
    {
        return true;
    }

    public function isDuplicate(
        string $rule,
        ?string $date = null,
        ?string $session = null,
        ?int $existingCount = 0
    ): bool {
        switch ($rule) {
            case 'once_ever':
                return $existingCount > 0;
            case 'once_per_day':
                return $existingCount > 0;
            case 'once_per_session':
                return $existingCount > 0;
            case 'multiple_allowed':
                return false;
            default:
                return $existingCount > 0;
        }
    }

    public function getLabel(string $rule): string
    {
        return match ($rule) {
            'once_ever' => 'Once Ever',
            'once_per_day' => 'Once Per Day',
            'once_per_session' => 'Once Per Session',
            'multiple_allowed' => 'Multiple Allowed',
            default => ucwords(str_replace('_', ' ', $rule)),
        };
    }

    public function getTypeLabel(string $type): string
    {
        return match ($type) {
            'transport' => 'Transport',
            'accommodation' => 'Accommodation',
            'entrance' => 'Entrance',
            'meal' => 'Meal',
            'activity' => 'Activity',
            'session' => 'Session',
            'other' => 'Other',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
