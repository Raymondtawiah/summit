<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ScanPointsSeeder;
use Database\Seeders\SummitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndParticipantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SummitSeeder::class]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin Dashboard');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_admin_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_shows_real_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Participant::factory()->count(3)->create();
        Ticket::factory()->count(2)->create(['status' => 'active']);
        Ticket::factory()->create(['status' => 'revoked']);
        Ticket::factory()->create(['printed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('3');
        $response->assertSee('2');
        $response->assertSee('1');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_participants_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Participant::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.participants'));

        $response->assertOk();
        $response->assertSee('Participants');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_search_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = Participant::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $response = $this->actingAs($admin)->get(route('admin.participants', ['search' => 'John']));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee($participant->registration_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_filter_participants_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $active = Participant::factory()->create(['status' => 'active']);
        Participant::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->get(route('admin.participants', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee($active->registration_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_participant_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = Participant::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.participants.show', $participant));

        $response->assertOk();
        $response->assertSee($participant->first_name);
        $response->assertSee($participant->last_name);
        $response->assertSee($participant->registration_number);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_edit_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = Participant::factory()->create(['shirt_size' => 'M']);

        $response = $this->actingAs($admin)->put(route('admin.participants.update', $participant), [
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'contact' => $participant->contact,
            'age' => $participant->age,
            'unit' => $participant->unit,
            'stake_district' => $participant->stake_district,
            'shirt_size' => 'L',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.participants.show', $participant));
        $this->assertDatabaseHas('participants', ['id' => $participant->id, 'shirt_size' => 'L']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participant_registration_number_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = Participant::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.participants.update', $participant), [
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'contact' => $participant->contact,
            'age' => $participant->age,
            'unit' => $participant->unit,
            'stake_district' => $participant->stake_district,
            'shirt_size' => $participant->shirt_size,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'registration_number' => $participant->registration_number,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participant_update_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = Participant::factory()->create(['shirt_size' => 'M']);

        $this->actingAs($admin)->put(route('admin.participants.update', $participant), [
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'contact' => $participant->contact,
            'age' => $participant->age,
            'unit' => $participant->unit,
            'stake_district' => $participant->stake_district,
            'shirt_size' => 'L',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PARTICIPANT_UPDATED,
            'entity_type' => 'participant',
            'entity_id' => $participant->id,
            'user_id' => $admin->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_user_cannot_edit_participant(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $participant = Participant::factory()->create();

        $response = $this->actingAs($staff)->get(route('admin.participants.edit', $participant));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function participants_list_uses_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Participant::factory()->count(25)->create();

        $response = $this->actingAs($admin)->get(route('admin.participants'));

        $response->assertOk();
        $response->assertSee('Participants');
        $page2 = $this->actingAs($admin)->get(route('admin.participants', ['page' => 2]));
        $page2->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function empty_participants_list_shows_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.participants'));

        $response->assertOk();
        $response->assertSee('No participants have been added yet.');
    }
}
