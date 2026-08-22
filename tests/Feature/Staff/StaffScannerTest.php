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
use Tests\TestCase;

class StaffScannerTest extends TestCase
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

    private function createTicket(User $staff = null): Ticket
    {
        $participant = Participant::factory()->create(['status' => 'active']);
        $scanPoint = $staff ? $staff->scanPoint : $this->createScanPoint();

        return Ticket::create([
            'participant_id' => $participant->id,
            'ticket_number' => 'TKT-2026-'.str_pad((string) Ticket::count() + 1, 6, '0', STR_PAD_LEFT),
            'qr_token' => 'LDS-SUMMITPASS:'.bin2hex(random_bytes(32)),
            'status' => 'active',
            'generated_at' => now(),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_access_scanner(): void
    {
        $response = $this->get(route('staff.scanner'));

        $response->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_can_access_scanner(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('staff.scanner'));

        $response->assertOk();
        $response->assertSee('SUMMIT STAFF SCANNER');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_access_admin_attendance(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.attendance'));

        $response->assertOk();
        $response->assertSee('Attendance Management');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_admin_attendance(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('admin.attendance'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_staff_cannot_scan(): void
    {
        $staff = $this->createStaff();
        $staff->update(['status' => 'inactive']);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => 'test-token',
        ]);

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_without_scan_point_cannot_scan(): void
    {
        $staff = $this->createStaff();
        $staff->update(['scan_point_id' => null]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => 'test-token',
        ]);

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_with_inactive_scan_point_cannot_scan(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $scanPoint->update(['status' => 'inactive']);
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => 'test-token',
        ]);

        $response->assertOk();
        $response->assertJson(['result' => 'access_inactive']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_qr_is_accepted(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => 'access_granted',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_qr_is_rejected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => 'invalid-token',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'result' => 'invalid',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revoked_ticket_is_rejected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);
        $ticket->update(['status' => 'revoked']);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'result' => 'revoked',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function replaced_ticket_is_rejected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);
        $ticket->update(['status' => 'replaced']);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'result' => 'replaced',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_scan_creates_attendance_record(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
            'scan_mode' => 'online',
            'sync_status' => 'synced',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_records_authenticated_staff(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_records_assigned_scan_point(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'scan_point_id' => $scanPoint->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function online_scan_has_correct_scan_mode(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'scan_mode' => 'online',
            'sync_status' => 'synced',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_scan_is_detected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'result' => 'duplicate',
        ]);

        $this->assertDatabaseCount('attendance_logs', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function different_scan_points_can_legitimately_record_attendance(): void
    {
        $staff = $this->createStaff();
        $scanPoint1 = $this->createScanPoint();
        $scanPoint2 = ScanPoint::factory()->create(['status' => 'active']);
        $staff->update(['scan_point_id' => $scanPoint1->id]);
        $ticket = $this->createTicket($staff);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $staff->update(['scan_point_id' => $scanPoint2->id]);
        $staff->refresh();

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => 'access_granted',
        ]);

        $this->assertDatabaseCount('attendance_logs', 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function public_user_cannot_create_attendance(): void
    {
        $ticket = $this->createTicket();

        $response = $this->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertRedirect(route('login'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_manipulate_scan_point(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $otherScanPoint = ScanPoint::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
            'scan_point_id' => $otherScanPoint->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'result' => 'access_granted',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'scan_point_id' => $scanPoint->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_manipulate_staff_id(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $otherStaff = User::factory()->create(['role' => 'staff', 'status' => 'active']);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
            'staff_id' => $otherStaff->id,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_payload_returns_validation_error(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => 12345,
        ]);

        $response->assertSessionHasErrors();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function successful_api_response_has_expected_structure(): void
    {
        $staff = $this->createStaff();
        $scanPoint = $this->createScanPoint();
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = $this->createTicket($staff);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'result',
            'message',
            'participant' => ['first_name', 'last_name', 'registration_number'],
            'ticket' => ['ticket_number', 'status'],
            'access' => ['name', 'type', 'rule'],
        ]);
    }
}
