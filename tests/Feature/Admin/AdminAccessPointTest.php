<?php

namespace Tests\Feature\Admin;

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

class AdminAccessPointTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createStaff(): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_access_point(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.scan-points.store'), [
            'name' => 'Bus Boarding',
            'code' => 'BUS-01',
            'type' => 'transport',
            'location' => 'Koforidua Station',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_ever',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('scan_points', [
            'name' => 'Bus Boarding',
            'code' => 'BUS-01',
            'type' => 'transport',
            'duplicate_rule' => 'once_ever',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_access_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['type' => 'entrance', 'duplicate_rule' => 'once_ever']);

        $response = $this->actingAs($admin)->put(route('admin.scan-points.update', $scanPoint), [
            'name' => 'Main Entrance Updated',
            'type' => 'entrance',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_per_day',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('scan_points', [
            'id' => $scanPoint->id,
            'name' => 'Main Entrance Updated',
            'duplicate_rule' => 'once_per_day',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_activate_deactivate_access_point(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->post(route('admin.scan-points.deactivate', $scanPoint));
        $this->assertDatabaseHas('scan_points', ['id' => $scanPoint->id, 'status' => 'inactive']);

        $this->actingAs($admin)->post(route('admin.scan-points.activate', $scanPoint));
        $this->assertDatabaseHas('scan_points', ['id' => $scanPoint->id, 'status' => 'active']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_edit_access_point(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create();

        $response = $this->actingAs($staff)->put(route('admin.scan-points.update', $scanPoint), [
            'name' => 'Hacked',
            'type' => 'entrance',
            'status' => 'active',
            'requires_ticket' => true,
            'duplicate_rule' => 'once_ever',
        ]);

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function once_ever_rule_prevents_second_scan(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create(['status' => 'active', 'duplicate_rule' => 'once_ever']);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response->assertOk();
        $response->assertJson(['result' => 'duplicate']);
        $this->assertDatabaseCount('attendance_logs', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function once_per_day_rule_allows_next_day_scan(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create(['status' => 'active', 'duplicate_rule' => 'once_per_day']);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);
        $response->assertJson(['result' => 'duplicate']);

        $firstScan = AttendanceLog::first();
        $this->travelTo($firstScan->scanned_at->addDay());

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);
        $response->assertJson(['result' => 'access_granted']);

        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_before_opening_is_rejected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create([
            'status' => 'active',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->travelTo(now()->setTime(9, 59));

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response->assertOk();
        $response->assertJson(['result' => 'access_closed']);

        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function scan_after_closing_is_rejected(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create([
            'status' => 'active',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->travelTo(now()->setTime(12, 1));

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response->assertOk();
        $response->assertJson(['result' => 'access_closed']);

        $this->travelBack();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_access_point_rejects_scan(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create(['status' => 'inactive']);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response->assertOk();
        $response->assertJson(['result' => 'access_inactive']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function bus_scan_does_not_prevent_lunch_scan(): void
    {
        $staff = $this->createStaff();
        $busPoint = ScanPoint::factory()->create(['name' => 'Bus Boarding', 'status' => 'active', 'duplicate_rule' => 'once_ever', 'type' => 'transport']);
        $lunchPoint = ScanPoint::factory()->create(['name' => 'Lunch', 'status' => 'active', 'duplicate_rule' => 'once_per_day', 'type' => 'meal']);
        $staff->update(['scan_point_id' => $busPoint->id]);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $staff->update(['scan_point_id' => $lunchPoint->id]);
        $staff->refresh();
        $response = $this->actingAs($staff)->post(route('staff.api.scan'), ['token' => $ticket->qr_token]);

        $response->assertOk();
        $response->assertJson(['result' => 'access_granted']);

        $this->assertDatabaseCount('attendance_logs', 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_manipulate_access_point(): void
    {
        $staff = $this->createStaff();
        $scanPoint = ScanPoint::factory()->create(['status' => 'active']);
        $staff->update(['scan_point_id' => $scanPoint->id]);
        $otherScanPoint = ScanPoint::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create(['status' => 'active']);

        $this->actingAs($staff)->post(route('staff.api.scan'), [
            'token' => $ticket->qr_token,
            'scan_point_id' => $otherScanPoint->id,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'ticket_id' => $ticket->id,
            'scan_point_id' => $scanPoint->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_attendance_dashboard_supports_filters(): void
    {
        $admin = $this->createAdmin();
        $scanPoint = ScanPoint::factory()->create(['type' => 'meal']);
        $ticket = Ticket::factory()->create(['status' => 'active']);
        AttendanceLog::factory()->create(['scan_point_id' => $scanPoint->id, 'ticket_id' => $ticket->id, 'result' => 'access_granted']);

        $response = $this->actingAs($admin)->get(route('admin.attendance', ['access_type' => 'meal']));

        $response->assertOk();
        $response->assertSee('Meal');
    }
}
