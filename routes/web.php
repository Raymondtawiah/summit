<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScanPointController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SyncController as AdminSyncController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Staff\ScanController;
use App\Http\Controllers\Staff\StaffController as StaffScannerController;
use App\Http\Controllers\Staff\SyncController as StaffSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/home', HomeController::class)->name('home.redirect');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/participants', [\App\Http\Controllers\Admin\ParticipantController::class, 'index'])->name('participants');
        Route::get('/participants/{participant}', [\App\Http\Controllers\Admin\ParticipantController::class, 'show'])->name('participants.show');
        Route::get('/participants/{participant}/edit', [\App\Http\Controllers\Admin\ParticipantController::class, 'edit'])->name('participants.edit');
        Route::put('/participants/{participant}', [\App\Http\Controllers\Admin\ParticipantController::class, 'update'])->name('participants.update');
        Route::get('/import', [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('import');
        Route::get('/import/create', [\App\Http\Controllers\Admin\ImportController::class, 'create'])->name('import.create');
        Route::post('/import', [\App\Http\Controllers\Admin\ImportController::class, 'store'])->name('import.store');
        Route::post('/import/confirm', [\App\Http\Controllers\Admin\ImportController::class, 'confirm'])->name('import.confirm');
        Route::get('/import/history', [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('import.history');
        Route::get('/import/history/{import}', [\App\Http\Controllers\Admin\ImportController::class, 'show'])->name('import.show');
        Route::get('/import/template', [\App\Http\Controllers\Admin\ImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
        Route::get('/tickets/{ticket}/pdf', [TicketController::class, 'pdf'])->name('tickets.pdf');
        Route::post('/tickets/{participant}/generate', [TicketController::class, 'generate'])->name('tickets.generate');
        Route::post('/tickets/bulk-generate', [TicketController::class, 'bulkGenerate'])->name('tickets.bulk-generate');
        Route::post('/tickets/generate-missing', [TicketController::class, 'generateMissing'])->name('tickets.generate-missing');
        Route::post('/tickets/{ticket}/revoke', [TicketController::class, 'revoke'])->name('tickets.revoke');
        Route::post('/tickets/{ticket}/replace', [TicketController::class, 'replace'])->name('tickets.replace');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/activate', [StaffController::class, 'activate'])->name('staff.activate');
        Route::post('/staff/{staff}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
        Route::post('/staff/{staff}/assign-scan-point', [StaffController::class, 'assignScanPoint'])->name('staff.assign-scan-point');
        Route::get('/scan-points', [ScanPointController::class, 'index'])->name('scan-points');
        Route::get('/scan-points/create', [ScanPointController::class, 'create'])->name('scan-points.create');
        Route::post('/scan-points', [ScanPointController::class, 'store'])->name('scan-points.store');
        Route::get('/scan-points/{scanPoint}', [ScanPointController::class, 'show'])->name('scan-points.show');
        Route::get('/scan-points/{scanPoint}/edit', [ScanPointController::class, 'edit'])->name('scan-points.edit');
        Route::put('/scan-points/{scanPoint}', [ScanPointController::class, 'update'])->name('scan-points.update');
        Route::post('/scan-points/{scanPoint}/activate', [ScanPointController::class, 'activate'])->name('scan-points.activate');
        Route::post('/scan-points/{scanPoint}/deactivate', [ScanPointController::class, 'deactivate'])->name('scan-points.deactivate');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
        Route::get('/synchronization', [AdminSyncController::class, 'index'])->name('synchronization');
        Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/participation', [ReportController::class, 'participation'])->name('reports.participation');
        Route::get('/reports/units', [ReportController::class, 'units'])->name('reports.units');
        Route::get('/reports/stakes', [ReportController::class, 'stakes'])->name('reports.stakes');
        Route::get('/reports/access-points', [ReportController::class, 'accessPoints'])->name('reports.access-points');
        Route::get('/reports/staff', [ReportController::class, 'staff'])->name('reports.staff');
        Route::get('/reports/devices', [ReportController::class, 'devices'])->name('reports.devices');
        Route::get('/reports/audit-logs', [ReportController::class, 'auditLogs'])->name('reports.audit-logs');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [StaffScannerController::class, 'dashboard'])->name('dashboard');
        Route::get('/scanner', [StaffScannerController::class, 'scanner'])->name('scanner');
        Route::post('/api/scan', [ScanController::class, 'scan'])->middleware('throttle:10,1')->name('api.scan');
        Route::get('/api/scans/today', [ScanController::class, 'todayScans'])->name('api.scans.today');
        Route::get('/scans', [StaffScannerController::class, 'scans'])->name('scans');
        Route::get('/api/sync/status', [StaffSyncController::class, 'status'])->name('api.sync.status');
        Route::post('/api/sync/download', [StaffSyncController::class, 'download'])->name('api.sync.download');
        Route::post('/api/sync/upload', [StaffSyncController::class, 'upload'])->middleware('throttle:30,1')->name('api.sync.upload');
        Route::get('/sync', [StaffSyncController::class, 'index'])->name('sync');
        Route::get('/profile', [StaffScannerController::class, 'profile'])->name('profile');
        Route::post('/profile/password', [StaffScannerController::class, 'updatePassword'])->name('profile.password');
    });

    Route::middleware(['auth'])->get('/dashboard', function () {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'staff') {
            return redirect()->route('staff.dashboard');
        }

        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
