<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            if (!$device->device_token) {
                $device->device_token = Str::random(64);
            }
        });
    }

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'uuid',
        'name',
        'device_identifier',
        'staff_id',
        'last_sync_at',
        'dataset_version',
        'data_invalidated',
        'status',
    ];

    protected $hidden = [
        'device_token',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'data_invalidated' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function syncBatches(): HasMany
    {
        return $this->hasMany(SyncBatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
