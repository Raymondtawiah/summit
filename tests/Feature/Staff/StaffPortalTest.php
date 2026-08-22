<?php

namespace Tests\Feature\Staff;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\ScanPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffPortalTest extends TestCase
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_is_redirected_to_dashboard_on_login(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('staff.dashboard'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_access_dashboard(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee('LDS SUMMITPASS');
        $response->assertSee('STAFF PORTAL');
        $response->assertSee($staff->name);
        $response->assertSee($scanPoint->name);
        $response->assertSee('Open Scanner');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_dashboard_shows_today_scan_stats(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $participant = \App\Models\Participant::factory()->create(['status' => 'active']);
        $ticket = \App\Models\Ticket::create([
            'participant_id' => $participant->id,
            'ticket_number' => 'TKT-2026-'.str_pad((string) (\App\Models\Ticket::count() + 1), 6, '0', STR_PAD_LEFT),
            'qr_token' => 'LDS-SUMMITPASS:'.bin2hex(random_bytes(32)),
            'status' => 'active',
            'generated_at' => now(),
        ]);

        AttendanceLog::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
            'result' => 'access_granted',
            'scanned_at' => now(),
            'scan_mode' => 'online',
            'sync_status' => 'synced',
        ]);

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee('LDS SUMMITPASS');
        $response->assertSee('STAFF PORTAL');
        $response->assertSee('Current Duty');
        $response->assertSee('Open Scanner');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_cannot_access_staff_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('staff.dashboard'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_access_profile(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('staff.profile'));

        $response->assertOk();
        $response->assertSee($staff->name);
        $response->assertSee($staff->email);
        $response->assertSee('Change Password');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_update_password(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->post(route('staff.profile.password'), [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('status');
        $staff->refresh();
        $this->assertTrue(Hash::check('newpassword123', $staff->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_update_password_with_wrong_current_password(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->post(route('staff.profile.password'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_cannot_access_staff_profile(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('staff.profile'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_access_sync_page(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->get(route('staff.sync'));

        $response->assertOk();
        $response->assertSee('Synchronization');
        $response->assertSee('Sync Now');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_cannot_access_staff_sync_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('staff.sync'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_scanner_page_shows_access_point(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->get(route('staff.scanner'));

        $response->assertOk();
        $response->assertSee($scanPoint->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_scanner_page_shows_connection_status(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->get(route('staff.scanner'));

        $response->assertOk();
        $response->assertSee('Online');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_admin_pages(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_sees_device_info_on_profile(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $device = Device::factory()->forStaff($staff)->create(['status' => 'active']);

        $response = $this->actingAs($staff)->get(route('staff.profile'));

        $response->assertOk();
        $response->assertSee($device->device_identifier);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_redirects_staff_to_staff_dashboard(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('staff.dashboard'));
    }
}
