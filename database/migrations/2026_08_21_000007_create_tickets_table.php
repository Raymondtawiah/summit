<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('qr_token')->unique();
            $table->string('status')->default('active');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('replaced_by_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_number');
            $table->index('qr_token');
            $table->index(['participant_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
