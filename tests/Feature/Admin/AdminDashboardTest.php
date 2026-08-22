<?php

namespace Tests\Feature\Admin;

use App\Models\AccessPoint;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Participant;
use App\Models\ScanPoint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_dashboard_loads_successfully(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Summit Dashboard');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_shows_correct_summary_counts(): void
    {
        $admin = $this->createAdmin();
        Participant::factory()->count(5)->create(['status' => 'active']);
        Participant::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('5');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_admin_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'password' => Hash::make('password123')]);

        $response = $this->actingAs($staff)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_attendance_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.attendance'));

        $response->assertOk();
        $response->assertSee('Attendance Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_participation_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.participation'));

        $response->assertOk();
        $response->assertSee('Participation Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_units_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.units'));

        $response->assertOk();
        $response->assertSee('Unit Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_stakes_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.stakes'));

        $response->assertOk();
        $response->assertSee('Stake/District Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_access_points_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.access-points'));

        $response->assertOk();
        $response->assertSee('Access Point Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_staff_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.staff'));

        $response->assertOk();
        $response->assertSee('Staff Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reports_devices_page_loads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.devices'));

        $response->assertOk();
        $response->assertSee('Device Report');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_reports(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active', 'password' => Hash::make('password123')]);

        $response = $this->actingAs($staff)->get(route('admin.reports.attendance'));

        $response->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function export_csv_downloads(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.export', ['type' => 'units']) . '?format=csv');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function export_requires_authorization(): void
    {
        $response = $this->get(route('admin.reports.export', ['type' => 'units']) . '?format=csv');

        $response->assertRedirect(route('login'));
    }
}
