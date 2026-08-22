<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('result')->nullable()->after('sync_status');
            $table->string('access_type')->nullable()->after('result');
            $table->string('attendance_rule')->nullable()->after('access_type');
            $table->timestamp('server_received_at')->nullable()->after('offline_created_at');
            $table->integer('sync_attempts')->default(0)->after('server_received_at');
            $table->text('sync_error')->nullable()->after('sync_attempts');
            $table->string('correction_reason')->nullable()->after('sync_error');
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete()->after('correction_reason');
            $table->timestamp('corrected_at')->nullable()->after('corrected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'result',
                'access_type',
                'attendance_rule',
                'server_received_at',
                'sync_attempts',
                'sync_error',
                'correction_reason',
                'corrected_by',
                'corrected_at',
            ]);
        });
    }
};
