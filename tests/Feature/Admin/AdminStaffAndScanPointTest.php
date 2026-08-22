<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\ScanPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffAndScanPointTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_staff(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'scan_point_id' => $scanPoint->id,
        ]);

        $response->assertRedirect(route('admin.staff'));
        $this->assertDatabaseHas('users', [
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_email_must_be_unique(): void
    {
        $admin = $this->createAdmin();
        User::factory()->create(['email' => 'existing@example.com', 'role' => 'staff']);

        $response = $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Duplicate Staff',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'scan_point_id' => null,
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_password_is_securely_hashed(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Test Staff',
            'email' => 'hashed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'scan_point_id' => null,
        ]);

        $staff = User::where('email', 'hashed@example.com')->first();
        $this->assertTrue(Hash::check('password123', $staff->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_role_is_automatically_staff(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Test Staff',
            'email' => 'role@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'scan_point_id' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'role@example.com',
            'role' => 'staff',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_be_activated(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'inactive']);

        $response = $this->actingAs($admin)->post(route('admin.staff.activate', $staff));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_be_deactivated(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);

        $response = $this->actingAs($admin)->post(route('admin.staff.deactivate', $staff));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_staff_cannot_log_in(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'inactive',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password123',
        ]);

        $this->assertGuest();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_staff(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Updated Name',
            'email' => $staff->email,
            'status' => 'active',
            'scan_point_id' => null,
        ]);

        $response->assertRedirect(route('admin.staff.show', $staff));
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Updated Name',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_reset_staff_password(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($admin)->post(route('admin.staff.reset-password', $staff), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('newpassword123', $staff->fresh()->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_scan_point(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.scan-points.store'), [
            'name' => 'Main Entrance',
            'code' => 'ENT-01',
            'type' => 'entrance',
            'location' => 'Building A',
            'description' => 'Main entrance scan point',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_ever',
        ]);

        $response->assertRedirect(route('admin.scan-points'));
        $this->assertDatabaseHas('scan_points', [
            'name' => 'Main Entrance',
            'location' => 'Building A',
            'type' => 'entrance',
            'duplicate_rule' => 'once_ever',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_scan_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['type' => 'entrance', 'duplicate_rule' => 'once_ever']);

        $response = $this->actingAs($admin)->put(route('admin.scan-points.update', $scanPoint), [
            'name' => 'Updated Name',
            'code' => $scanPoint->code,
            'type' => 'entrance',
            'location' => 'Building B',
            'description' => 'Updated description',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_per_day',
        ]);

        $response->assertRedirect(route('admin.scan-points.show', $scanPoint));
        $this->assertDatabaseHas('scan_points', [
            'id' => $scanPoint->id,
            'name' => 'Updated Name',
            'duplicate_rule' => 'once_per_day',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_activate_scan_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->post(route('admin.scan-points.activate', $scanPoint));

        $response->assertRedirect();
        $this->assertDatabaseHas('scan_points', [
            'id' => $scanPoint->id,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_deactivate_scan_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->post(route('admin.scan-points.deactivate', $scanPoint));

        $response->assertRedirect();
        $this->assertDatabaseHas('scan_points', [
            'id' => $scanPoint->id,
            'status' => 'inactive',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_staff_can_share_scan_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create();
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $staff1->update(['scan_point_id' => $scanPoint->id]);
        $staff2->update(['scan_point_id' => $scanPoint->id]);

        $this->assertEquals(2, $scanPoint->users()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_have_only_one_active_scan_point_assignment(): void
    {
        $admin = $this->createAdmin();
        $scanPoint1 = ScanPoint::factory()->create();
        $scanPoint2 = ScanPoint::factory()->create();
        $staff = User::factory()->create(['role' => 'staff']);

        $staff->update(['scan_point_id' => $scanPoint1->id]);
        $staff->update(['scan_point_id' => $scanPoint2->id]);

        $this->assertEquals($scanPoint2->id, $staff->fresh()->scan_point_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function active_staff_with_active_scan_point_returns_ready(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);
        $scanPoint = ScanPoint::factory()->create(['status' => 'active']);
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $result = app(\App\Services\StaffAuthorizationService::class)->canStaffScan($staff);

        $this->assertTrue($result['ready']);
        $this->assertEquals('READY', $result['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_with_no_scan_point_cannot_scan(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'scan_point_id' => null]);

        $result = app(\App\Services\StaffAuthorizationService::class)->canStaffScan($staff);

        $this->assertFalse($result['ready']);
        $this->assertEquals('no_scan_point', $result['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_assigned_to_inactive_scan_point_can_authorize_but_access_control_blocks(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);
        $scanPoint = ScanPoint::factory()->create(['status' => 'inactive']);
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $result = app(\App\Services\StaffAuthorizationService::class)->canStaffScan($staff);

        $this->assertTrue($result['ready']);
        $this->assertEquals('READY', $result['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_admin_routes(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('admin.staff'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_manage_all_staff_and_scan_points(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff']);
        $scanPoint = ScanPoint::factory()->create();

        $this->actingAs($admin);

        $this->get(route('admin.staff'))->assertOk();
        $this->get(route('admin.staff.show', $staff))->assertOk();
        $this->get(route('admin.scan-points'))->assertOk();
        $this->get(route('admin.scan-points.show', $scanPoint))->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_creation_creates_audit_log(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Audit Staff',
            'email' => 'audit@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'scan_point_id' => $scanPoint->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_STAFF_CREATED,
            'entity_type' => 'user',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_status_change_creates_audit_log(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.staff.deactivate', $staff));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_STAFF_DEACTIVATED,
            'entity_type' => 'user',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_point_creation_creates_audit_log(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.scan-points.store'), [
            'name' => 'Audit Point',
            'code' => 'AUDIT-01',
            'type' => 'entrance',
            'location' => 'Building C',
            'description' => 'Test',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_ever',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_SCAN_POINT_CREATED,
            'entity_type' => 'scan_point',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_point_assignment_creates_audit_log(): void
    {
        $admin = $this->createAdmin();
        $staff = User::factory()->create(['role' => 'staff']);
        $scanPoint = ScanPoint::factory()->create();

        $this->actingAs($admin)->post(route('admin.staff.assign-scan-point', $staff), [
            'scan_point_id' => $scanPoint->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff_scan_point_assigned',
            'entity_type' => 'user',
        ]);
    }
}
