<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function scanPoints(): HasMany
    {
        return $this->hasMany(ScanPoint::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
