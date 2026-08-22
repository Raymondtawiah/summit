<?php

namespace Tests\Feature\Staff;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createScanPoint(): ScanPoint
    {
        return ScanPoint::factory()->create(['status' => 'active']);
    }

    private function createTicket(): Ticket
    {
        $participant = Participant::factory()->create(['status' => 'active']);

        return Ticket::create([
            'participant_id' => $participant->id,
            'ticket_number' => 'TKT-2026-'.str_pad((string) (Ticket::count() + 1), 6, '0', STR_PAD_LEFT),
            'qr_token' => 'LDS-SUMMITPASS:'.bin2hex(random_bytes(32)),
            'status' => 'active',
            'generated_at' => now(),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_status_requires_authentication(): void
    {
        $response = $this->get(route('staff.api.sync.status'));

        $response->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_download_requires_authentication(): void
    {
        $response = $this->post(route('staff.api.sync.download'));

        $response->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_requires_authentication(): void
    {
        $response = $this->post(route('staff.api.sync.upload'));

        $response->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_get_sync_status(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);

        $response = $this->actingAs($staff)->get(route('staff.api.sync.status'), [
            'device_identifier' => $device->device_identifier,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'dataset_version',
            'last_sync_at',
            'device_version',
            'update_available',
            'data_invalidated',
            'device_uuid',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_download_requires_authorized_device(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->post(route('staff.api.sync.download'));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_download_returns_data_for_authorized_device(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();

        $response = $this->actingAs($staff)->post(route('staff.api.sync.download'), [
            'device_identifier' => $device->device_identifier,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'dataset_version',
            'participants',
            'tickets',
            'access_points',
            'meta',
        ]);
        $this->assertNotEmpty($response->json('tickets'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_requires_authorized_device(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'records' => [],
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_accepts_valid_records(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseCount('attendance_logs', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_prevents_duplicate_local_uuid(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();
        $localUuid = (string) Str::uuid();

        $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => $localUuid,
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => $localUuid,
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('attendance_logs', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_rejects_revoked_ticket(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();
        $ticket->update(['status' => 'revoked']);

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response->assertOk();
        $results = $response->json('results');
        $this->assertEquals('rejected', $results[0]['status']);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_synchronization_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.synchronization'));

        $response->assertOk();
        $response->assertSee('Total Devices');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_view_synchronization_dashboard(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('admin.synchronization'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalidated_device_cannot_sync(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active', 'data_invalidated' => true]);
        $ticket = $this->createTicket();

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_preserves_client_timestamp(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();
        $clientTime = '2026-08-22 10:15:30';

        $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'local_uuid' => (string) Str::uuid(),
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => $clientTime,
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'offline_created_at' => $clientTime,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sync_upload_rejects_missing_local_uuid(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);
        $ticket = $this->createTicket();

        $response = $this->actingAs($staff)->post(route('staff.api.sync.upload'), [
            'device_identifier' => $device->device_identifier,
            'records' => [
                [
                    'ticket_id' => $ticket->id,
                    'participant_id' => $ticket->participant_id,
                    'scanned_at' => now()->toIso8601String(),
                    'scan_mode' => 'offline',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('records.0.local_uuid');
    }
}
