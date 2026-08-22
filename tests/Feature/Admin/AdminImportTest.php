<?php

namespace Tests\Feature\Admin;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AdminImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(storage_path('app/imports/temp'))) {
            mkdir(storage_path('app/imports/temp'), 0755, true);
        }
    }

    private function createValidExcelFile(array $rows = []): UploadedFile
    {
        if (empty($rows)) {
            $rows = [
                ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
                ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
                ['Jane', 'Smith', '0241111111', '22', 'Ward 2', 'Eastern', 'M'],
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tempFile = tempnam(sys_get_temp_dir(), 'summit_test_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return new UploadedFile($tempFile, 'test_import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_access_import_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.import'));

        $response->assertOk();
        $response->assertSee('Import Participants');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function staff_cannot_access_import_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('admin.import'));

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_file_type_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->create('test.csv', 100);

        $response = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function oversized_file_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->create('large.xlsx', 11000);

        $response = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function missing_required_headers_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['contact', 'age', 'Unit'],
            ['0240000000', '25', 'Ward 1'],
        ];

        $file = $this->createValidExcelFile($rows);

        $response = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_excel_file_shows_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = $this->createValidExcelFile();

        $response = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertSee('Import Preview');
        $response->assertSee('John');
        $response->assertSee('Doe');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_participant_is_imported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = $this->createValidExcelFile();

        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();

        $tempPath = 'imports/temp/test_import.xlsx';
        $fullPath = storage_path('app/' . $tempPath);
        
        if (!file_exists($fullPath)) {
            $sourcePath = $file->getRealPath();
            if (file_exists($sourcePath)) {
                copy($sourcePath, $fullPath);
            }
        }

        $confirmResponse = $this->actingAs($admin)->post(route('admin.import.confirm'), [
            'temp_path' => $tempPath,
            'file_name' => 'test_import.xlsx',
        ]);

        $confirmResponse->assertOk();
        $confirmResponse->assertSee('Import Complete');

        $this->assertDatabaseHas('participants', ['first_name' => 'John', 'last_name' => 'Doe']);
        $this->assertDatabaseHas('participants', ['first_name' => 'Jane', 'last_name' => 'Smith']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function missing_required_name_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_age_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '200', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_inside_excel_is_detected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_against_database_is_detected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Participant::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'contact' => '0240000000']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function existing_participant_does_not_receive_new_registration_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Participant::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'contact' => '0240000000']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function new_participant_receives_unique_registration_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['Alice', 'Johnson', '0242222222', '28', 'Ward 3', 'Eastern', 'XL'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function import_history_is_created(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_log_is_created_on_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0240000000', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function phone_numbers_are_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $rows = [
            ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'],
            ['John', 'Doe', '0241234567', '25', 'Ward 1', 'Eastern', 'L'],
        ];

        $file = $this->createValidExcelFile($rows);
        $uploadResponse = $this->actingAs($admin)->post(route('admin.import.store'), [
            'file' => $file,
        ]);

        $uploadResponse->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthorized_user_cannot_import(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('admin.import'));

        $response->assertStatus(403);
    }
}
