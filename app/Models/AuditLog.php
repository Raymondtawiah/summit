<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $timestamps = true;

    const ACTION_PARTICIPANT_IMPORTED = 'participant_imported';
    const ACTION_PARTICIPANT_UPDATED = 'participant_updated';
    const ACTION_TICKET_GENERATED = 'ticket_generated';
    const ACTION_TICKET_PRINTED = 'ticket_printed';
    const ACTION_TICKET_REVOKED = 'ticket_revoked';
    const ACTION_TICKET_REPLACED = 'ticket_replaced';
    const ACTION_STAFF_CREATED = 'staff_created';
    const ACTION_STAFF_UPDATED = 'staff_updated';
    const ACTION_STAFF_DEACTIVATED = 'staff_deactivated';
    const ACTION_SCAN_POINT_CREATED = 'scan_point_created';
    const ACTION_SCAN_POINT_UPDATED = 'scan_point_updated';
    const ACTION_SCAN_VALID = 'scan_valid';
    const ACTION_SCAN_INVALID = 'scan_invalid';
    const ACTION_SCAN_REVOKED = 'scan_revoked';
    const ACTION_SCAN_REPLACED = 'scan_replaced';
    const ACTION_SCAN_DUPLICATE = 'scan_duplicate';
    const ACTION_SCAN_INACTIVE_PARTICIPANT = 'scan_inactive_participant';

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
    ];

    protected $hidden = [
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
