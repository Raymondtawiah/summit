<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScanPoint extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'type',
        'location',
        'description',
        'status',
        'requires_ticket',
        'duplicate_rule',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'capacity',
    ];

protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'event_id' => 'integer',
            'capacity' => 'integer',
            'requires_ticket' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'scan_point_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'assigned_scan_point_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
