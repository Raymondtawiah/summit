<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminTicketTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createParticipant(): Participant
    {
        return Participant::factory()->create(['status' => 'active']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_access_ticket_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.tickets'));

        $response->assertOk();
        $response->assertSee('Ticket Management');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_can_be_generated_for_participant(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $response = $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'participant_id' => $participant->id,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_number_is_unique(): void
    {
        $admin = $this->createAdmin();
        $participant1 = $this->createParticipant();
        $participant2 = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant1));
        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant2));

        $ticket1 = Ticket::where('participant_id', $participant1->id)->first();
        $ticket2 = Ticket::where('participant_id', $participant2->id)->first();

        $this->assertNotEquals($ticket1->ticket_number, $ticket2->ticket_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function qr_token_is_unique(): void
    {
        $admin = $this->createAdmin();
        $participant1 = $this->createParticipant();
        $participant2 = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant1));
        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant2));

        $ticket1 = Ticket::where('participant_id', $participant1->id)->first();
        $ticket2 = Ticket::where('participant_id', $participant2->id)->first();

        $this->assertNotEquals($ticket1->qr_token, $ticket2->qr_token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function qr_token_is_cryptographically_random(): void
    {
        $admin = $this->createAdmin();
        $participant1 = $this->createParticipant();
        $participant2 = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant1));
        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant2));

        $ticket1 = Ticket::where('participant_id', $participant1->id)->first();
        $ticket2 = Ticket::where('participant_id', $participant2->id)->first();

        $this->assertStringStartsWith('LDS-SUMMITPASS:', $ticket1->qr_token);
        $this->assertStringStartsWith('LDS-SUMMITPASS:', $ticket2->qr_token);
        $this->assertNotEquals($ticket1->qr_token, $ticket2->qr_token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participant_cannot_have_two_active_tickets(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));

        $activeTickets = Ticket::where('participant_id', $participant->id)->where('status', 'active')->count();
        $this->assertEquals(1, $activeTickets);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function repeated_ticket_generation_does_not_create_duplicates(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $response1 = $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $response2 = $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));

        $this->assertDatabaseCount('tickets', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function bulk_generation_skips_participants_with_active_tickets(): void
    {
        $admin = $this->createAdmin();
        $participant1 = $this->createParticipant();
        $participant2 = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant1));

        $response = $this->actingAs($admin)->post(route('admin.tickets.bulk-generate'), [
            'participant_ids' => [$participant1->id, $participant2->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('tickets', 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_can_be_revoked(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($admin)->post(route('admin.tickets.revoke', $ticket));

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'revoked',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revoked_ticket_cannot_be_considered_valid(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();
        $this->actingAs($admin)->post(route('admin.tickets.revoke', $ticket));

        $verification = app(\App\Services\TicketVerificationService::class)->verify($ticket->qr_token);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('ticket_revoked', $verification['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_can_be_replaced(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $oldTicket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($admin)->post(route('admin.tickets.replace', $oldTicket));

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $oldTicket->id,
            'status' => 'replaced',
        ]);

        $newTicket = Ticket::where('participant_id', $participant->id)
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($newTicket);
        $this->assertNotEquals($oldTicket->qr_token, $newTicket->qr_token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function replaced_ticket_cannot_be_considered_valid(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $oldTicket = Ticket::where('participant_id', $participant->id)->first();
        $this->actingAs($admin)->post(route('admin.tickets.replace', $oldTicket));

        $verification = app(\App\Services\TicketVerificationService::class)->verify($oldTicket->qr_token);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('ticket_replaced', $verification['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function replacement_creates_new_qr_token(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $oldTicket = Ticket::where('participant_id', $participant->id)->first();
        $oldQrToken = $oldTicket->qr_token;

        $this->actingAs($admin)->post(route('admin.tickets.replace', $oldTicket));

        $newTicket = Ticket::where('participant_id', $participant->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotEquals($oldQrToken, $newTicket->qr_token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registration_number_does_not_change_when_ticket_is_replaced(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $oldTicket = Ticket::where('participant_id', $participant->id)->first();
        $oldRegistrationNumber = $participant->registration_number;

        $this->actingAs($admin)->post(route('admin.tickets.replace', $oldTicket));

        $this->assertEquals($oldRegistrationNumber, $participant->fresh()->registration_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function old_ticket_remains_in_database_after_replacement(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $oldTicket = Ticket::where('participant_id', $participant->id)->first();
        $oldTicketId = $oldTicket->id;

        $this->actingAs($admin)->post(route('admin.tickets.replace', $oldTicket));

        $this->assertDatabaseHas('tickets', [
            'id' => $oldTicketId,
            'status' => 'replaced',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reprinting_does_not_create_another_ticket(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();
        $initialCount = Ticket::count();

        $this->actingAs($admin)->get(route('admin.tickets.print', $ticket));

        $this->assertEquals($initialCount, Ticket::count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_pdf_can_be_generated(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($admin)->get(route('admin.tickets.pdf', $ticket));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $response->assertHeaderContains('Content-Disposition', 'attachment; filename="ticket-'.$ticket->ticket_number.'.html"');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_contains_qr_code(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($admin)->get(route('admin.tickets.show', $ticket));

        $response->assertOk();
        $response->assertSee($ticket->qr_token);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_staff_cannot_generate_tickets(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $participant = $this->createParticipant();

        $response = $this->actingAs($staff)->post(route('admin.tickets.generate', $participant));

        $response->assertForbidden();
        $this->assertDatabaseCount('tickets', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_staff_cannot_revoke_tickets(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($staff)->post(route('admin.tickets.revoke', $ticket));

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_staff_cannot_replace_tickets(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $response = $this->actingAs($staff)->post(route('admin.tickets.replace', $ticket));

        $response->assertForbidden();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_logs_are_created_on_ticket_generation(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TICKET_GENERATED,
            'entity_type' => 'ticket',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_logs_are_created_on_ticket_revocation(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $this->actingAs($admin)->post(route('admin.tickets.revoke', $ticket));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TICKET_REVOKED,
            'entity_type' => 'ticket',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_logs_are_created_on_ticket_replacement(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $this->actingAs($admin)->post(route('admin.tickets.replace', $ticket));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TICKET_REPLACED,
            'entity_type' => 'ticket',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_verification_service_correctly_identifies_valid_tickets(): void
    {
        $admin = $this->createAdmin();
        $participant = $this->createParticipant();

        $this->actingAs($admin)->post(route('admin.tickets.generate', $participant));
        $ticket = Ticket::where('participant_id', $participant->id)->first();

        $verification = app(\App\Services\TicketVerificationService::class)->verify($ticket->qr_token);

        $this->assertTrue($verification['valid']);
        $this->assertNotNull($verification['ticket']);
        $this->assertNotNull($verification['participant']);
        $this->assertEquals($ticket->id, $verification['ticket']->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ticket_verification_service_correctly_identifies_invalid_tickets(): void
    {
        $verification = app(\App\Services\TicketVerificationService::class)->verify('invalid-token');

        $this->assertFalse($verification['valid']);
        $this->assertEquals('token_not_found', $verification['reason']);
        $this->assertNull($verification['ticket']);
        $this->assertNull($verification['participant']);
    }
}
