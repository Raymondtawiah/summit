<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Castable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Attributes\With;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'registration_number',
        'first_name',
        'last_name',
        'contact',
        'age',
        'unit',
        'stake_district',
        'shirt_size',
        'assigned_scan_point_id',
        'status',
    ];

    #[Hidden(['contact'])]
    protected $hidden = [
        'contact',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'age' => 'integer',
            'assigned_scan_point_id' => 'integer',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function activeTicket(): HasOne
    {
        return $this->hasOne(Ticket::class)->where('status', 'active');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function assignedScanPoint(): BelongsTo
    {
        return $this->belongsTo(ScanPoint::class, 'assigned_scan_point_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
