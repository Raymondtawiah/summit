<?php

namespace Tests\Feature\Database;

use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\SyncBatch;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ScanPointsSeeder;
use Database\Seeders\SummitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummitDatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_participant_with_unique_registration_number(): void
    {
        $participant = Participant::factory()->create([
            'registration_number' => 'SUM-2026-000001',
        ]);

        $this->assertDatabaseHas('participants', [
            'registration_number' => 'SUM-2026-000001',
            'first_name' => $participant->first_name,
        ]);

        $this->assertNotNull($participant->registration_number);
        $this->assertMatchesRegularExpression('/^SUM-\d{4}-\d{6}$/', $participant->registration_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_enforces_unique_registration_number(): void
    {
        Participant::factory()->create(['registration_number' => 'SUM-2026-000001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Participant::factory()->create(['registration_number' => 'SUM-2026-000001']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_ticket_with_unique_ticket_number_and_qr_token(): void
    {
        $ticket = Ticket::factory()->create([
            'ticket_number' => 'TKT-2026-000001',
            'qr_token' => bin2hex(random_bytes(32)),
        ]);

        $this->assertDatabaseHas('tickets', [
            'ticket_number' => 'TKT-2026-000001',
        ]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ticket->qr_token);
        $this->assertSame(64, strlen($ticket->qr_token));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_enforces_unique_ticket_number(): void
    {
        Ticket::factory()->create(['ticket_number' => 'TKT-2026-000001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Ticket::factory()->create(['ticket_number' => 'TKT-2026-000001']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_enforces_unique_qr_token(): void
    {
        $token = bin2hex(random_bytes(32));
        Ticket::factory()->create(['qr_token' => $token]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Ticket::factory()->create(['qr_token' => $token]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participant_has_many_tickets(): void
    {
        $participant = Participant::factory()->create();
        $ticket1 = Ticket::factory()->create(['participant_id' => $participant->id]);
        $ticket2 = Ticket::factory()->create(['participant_id' => $participant->id]);

        $this->assertCount(2, $participant->tickets);
        $this->assertTrue($participant->tickets->contains($ticket1));
        $this->assertTrue($participant->tickets->contains($ticket2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participant_has_many_attendance_logs(): void
    {
        $participant = Participant::factory()->create();
        $ticket = Ticket::factory()->create(['participant_id' => $participant->id]);
        $staff = User::factory()->create(['role' => 'staff']);
        $scanPoint = ScanPoint::factory()->create();

        AttendanceLog::factory()->create([
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
        ]);

        AttendanceLog::factory()->create([
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
        ]);

        $this->assertCount(2, $participant->attendanceLogs);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_belongs_to_scan_point(): void
    {
        $scanPoint = ScanPoint::factory()->create();
        $staff = User::factory()->create([
            'role' => 'staff',
            'scan_point_id' => $scanPoint->id,
        ]);

        $this->assertEquals($scanPoint->id, $staff->scanPoint->id);
        $this->assertTrue($scanPoint->users->contains($staff));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function attendance_log_belongs_to_scan_point(): void
    {
        $scanPoint = ScanPoint::factory()->create();
        $participant = Participant::factory()->create();
        $ticket = Ticket::factory()->create(['participant_id' => $participant->id]);
        $staff = User::factory()->create(['role' => 'staff']);

        $attendanceLog = AttendanceLog::factory()->create([
            'scan_point_id' => $scanPoint->id,
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
        ]);

        $this->assertEquals($scanPoint->id, $attendanceLog->scanPoint->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_enforces_unique_attendance_uuid(): void
    {
        $uuid = fake()->uuid();
        $participant = Participant::factory()->create();
        $ticket = Ticket::factory()->create(['participant_id' => $participant->id]);
        $staff = User::factory()->create(['role' => 'staff']);
        $scanPoint = ScanPoint::factory()->create();

        AttendanceLog::factory()->create([
            'uuid' => $uuid,
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AttendanceLog::factory()->create([
            'uuid' => $uuid,
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'scan_point_id' => $scanPoint->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_can_be_replaced_with_history_preserved(): void
    {
        $participant = Participant::factory()->create();
        $oldTicket = Ticket::factory()->create(['participant_id' => $participant->id]);
        $newTicket = Ticket::factory()->create(['participant_id' => $participant->id]);

        $oldTicket->update([
            'status' => 'replaced',
            'replaced_by_ticket_id' => $newTicket->id,
        ]);

        $this->assertEquals('replaced', $oldTicket->refresh()->status);
        $this->assertEquals($newTicket->id, $oldTicket->refresh()->replaced_by_ticket_id);
        $this->assertDatabaseHas('tickets', ['id' => $oldTicket->id, 'status' => 'replaced']);
        $this->assertDatabaseHas('tickets', ['id' => $newTicket->id, 'status' => 'active']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_log_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create([
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_PARTICIPANT_IMPORTED,
            'entity_type' => 'participant',
            'entity_id' => 1,
            'description' => 'Imported 50 participants from Excel',
            'old_values' => null,
            'new_values' => ['count' => 50],
        ]);

        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals('participant_imported', $auditLog->action);
        $this->assertEquals('participant', $auditLog->entity_type);
        $this->assertEquals(1, $auditLog->entity_id);
        $this->assertEquals(['count' => 50], $auditLog->new_values);
        $this->assertNull($auditLog->old_values);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_scan_points_via_seeder(): void
    {
        $this->seed([SummitSeeder::class]);

        $this->assertDatabaseHas('scan_points', ['name' => 'Main Entrance']);
        $this->assertDatabaseHas('scan_points', ['name' => 'Bus Boarding']);
        $this->assertDatabaseHas('scan_points', ['name' => 'Accommodation']);
        $this->assertDatabaseHas('scan_points', ['name' => 'Meals']);
        $this->assertDatabaseHas('scan_points', ['name' => 'Main Hall']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_working_scan_point_relationships(): void
    {
        $scanPoint = ScanPoint::factory()->create();
        $staff = User::factory()->create(['role' => 'staff', 'scan_point_id' => $scanPoint->id]);
        $participant = Participant::factory()->create(['assigned_scan_point_id' => $scanPoint->id]);
        $ticket = Ticket::factory()->create(['participant_id' => $participant->id]);
        $device = Device::factory()->forStaff($staff)->create();

        AttendanceLog::factory()->create([
            'scan_point_id' => $scanPoint->id,
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'staff_id' => $staff->id,
            'device_id' => $device->id,
        ]);

        $this->assertTrue($scanPoint->users->contains($staff));
        $this->assertTrue($scanPoint->participants->contains($participant));
        $this->assertTrue($scanPoint->attendanceLogs->isNotEmpty());
        $this->assertTrue($staff->devices->contains($device));
        $this->assertTrue($staff->attendanceLogs->isNotEmpty());
    }
}
