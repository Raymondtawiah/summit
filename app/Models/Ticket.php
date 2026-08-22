<?php

namespace App\Models;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererBuilder;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Attributes\MassAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (!$ticket->qr_token) {
                $ticket->qr_token = 'LDS-SUMMITPASS:'.bin2hex(random_bytes(32));
            }
        });
    }

    #[MassAssignment\MassAssignment]
    protected $fillable = [
        'participant_id',
        'ticket_number',
        'status',
        'generated_at',
        'printed_at',
        'revoked_at',
        'replaced_by_ticket_id',
    ];

    protected $hidden = [
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'printed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'deleted_at' => 'datetime',
            'participant_id' => 'integer',
            'replaced_by_ticket_id' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'replaced_by_ticket_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeReplaced($query)
    {
        return $query->where('status', 'replaced');
    }

    public function qrCodeImage(int $size = 200): string
    {
        $renderer = new \BaconQrCode\Renderer\GDLibRenderer($size);
        $writer = new Writer($renderer);

        return base64_encode($writer->writeString($this->qr_token));
    }
}

