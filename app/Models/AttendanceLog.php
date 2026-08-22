<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'uuid',
        'local_uuid',
        'participant_id',
        'ticket_id',
        'staff_id',
        'scan_point_id',
        'device_id',
        'scanned_at',
        'scan_mode',
        'sync_status',
        'offline_created_at',
        'result',
        'access_type',
        'attendance_rule',
        'server_received_at',
        'sync_attempts',
        'sync_error',
        'correction_reason',
        'corrected_at',
    ];

    protected $hidden = [
        'local_uuid',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'offline_created_at' => 'datetime',
            'server_received_at' => 'datetime',
            'corrected_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function scanPoint(): BelongsTo
    {
        return $this->belongsTo(ScanPoint::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('scan_mode', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('scan_mode', 'offline');
    }

    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
