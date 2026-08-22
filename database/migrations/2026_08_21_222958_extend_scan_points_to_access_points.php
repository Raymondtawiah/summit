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
        Schema::table('scan_points', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->string('code')->nullable()->unique()->after('event_id');
            $table->string('type')->default('other')->after('code');
            $table->boolean('requires_ticket')->default(true)->after('type');
            $table->string('duplicate_rule')->default('once_ever')->after('requires_ticket');
            $table->date('start_date')->nullable()->after('duplicate_rule');
            $table->date('end_date')->nullable()->after('start_date');
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->integer('capacity')->nullable()->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_points', function (Blueprint $table) {
            $table->dropColumn([
                'event_id',
                'code',
                'type',
                'requires_ticket',
                'duplicate_rule',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'capacity',
            ]);
        });
    }
};
