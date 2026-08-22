<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('unit')->nullable();
            $table->string('stake_district')->nullable();
            $table->string('shirt_size')->nullable();
            $table->foreignId('assigned_scan_point_id')->nullable()->constrained('scan_points')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('registration_number');
            $table->index('contact');
            $table->index(['last_name', 'first_name']);
            $table->index(['stake_district', 'unit']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
