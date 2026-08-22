<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scan_point_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->string('scan_mode')->default('online');
            $table->string('sync_status')->default('pending');
            $table->timestamp('offline_created_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index(['participant_id', 'scanned_at']);
            $table->index(['ticket_id', 'scanned_at']);
            $table->index(['staff_id', 'scanned_at']);
            $table->index(['scan_point_id', 'scanned_at']);
            $table->index(['device_id', 'sync_status']);
            $table->index(['sync_status', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
