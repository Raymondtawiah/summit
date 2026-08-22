<?php

namespace App\Services\Import;

use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ParticipantImport;
use App\Models\User;
use App\Services\RegistrationNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ParticipantImportService
{
    protected array $headerMap = [
        'fname' => 'first_name',
        'first name' => 'first_name',
        'firstname' => 'first_name',
        'first_name' => 'first_name',
        'lname' => 'last_name',
        'last name' => 'last_name',
        'lastname' => 'last_name',
        'last_name' => 'last_name',
        'contact' => 'contact',
        'phone' => 'contact',
        'phone number' => 'contact',
        'phone_number' => 'contact',
        'age' => 'age',
        'unit' => 'unit',
        'stake/district' => 'stake_district',
        'stake district' => 'stake_district',
        'stake_district' => 'stake_district',
        'stakedistrict' => 'stake_district',
        'stake' => 'stake_district',
        'shirt size' => 'shirt_size',
        'shirt_size' => 'shirt_size',
        'shirtsize' => 'shirt_size',
        'shirt' => 'shirt_size',
    ];

    protected array $requiredHeaders = ['first_name', 'last_name'];

    protected array $allowedExtensions = ['xlsx'];

    protected int $maxFileSize = 10240; // 10MB

    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $errors[] = 'Invalid file type. Only .xlsx files are allowed.';
        }

        if ($file->getSize() > $this->maxFileSize * 1024) {
            $errors[] = "File size exceeds maximum limit of {$this->maxFileSize}KB.";
        }

        try {
            $reader = new XlsxReader();
            $reader->canRead($file->getRealPath());
        } catch (\Throwable $e) {
            $errors[] = 'Unable to read the Excel file. Please ensure it is a valid .xlsx file.';
        }

        return $errors;
    }

    public function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        $missing = [];

        foreach ($headers as $header) {
            $clean = strtolower(trim((string) $header));
            $clean = preg_replace('/[^a-z0-9]/', '', $clean) ?: $clean;
            $mapped = $this->headerMap[$clean] ?? null;
            if ($mapped) {
                $normalized[] = $mapped;
            } else {
                $normalized[] = null;
            }
        }

        foreach ($this->requiredHeaders as $required) {
            if (!in_array($required, $normalized)) {
                $missing[] = $required;
            }
        }

        return [
            'normalized' => $normalized,
            'missing' => $missing,
            'valid' => empty($missing),
        ];
    }

    public function readPreview(UploadedFile $file, int $maxRows = 100): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headerRow = array_shift($rows);
        $headers = array_values($headerRow);
        $normalization = $this->normalizeHeaders($headers);

        $previewRows = [];
        $rowNumber = 1;
        foreach ($rows as $row) {
            if ($rowNumber > $maxRows) {
                break;
            }
            $previewRows[] = array_values($row);
            $rowNumber++;
        }

        return [
            'headers' => $headers,
            'normalized_headers' => $normalization['normalized'],
            'rows' => $previewRows,
            'total_rows' => count($rows),
            'missing_headers' => $normalization['missing'],
            'valid_headers' => $normalization['valid'],
        ];
    }

    public function detectDuplicates(array $row): array
    {
        $results = [
            'type' => 'new',
            'existing_participant' => null,
            'confidence' => null,
        ];

        $contact = $this->normalizeContact($row['contact'] ?? null);
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));

        if ($contact) {
            $existing = Participant::where('contact', $contact)->first();
            if ($existing) {
                $results['type'] = 'possible_duplicate';
                $results['existing_participant'] = $existing;
                $results['confidence'] = 'high';
                return $results;
            }
        }

        if ($firstName && $lastName) {
            $query = Participant::where('first_name', $firstName)
                ->where('last_name', $lastName);

            if ($contact) {
                $query->where('contact', $contact);
            }

            $existing = $query->first();

            if ($existing) {
                $results['type'] = 'possible_duplicate';
                $results['existing_participant'] = $existing;
                $results['confidence'] = $contact ? 'high' : 'medium';
                return $results;
            }
        }

        return $results;
    }

    public function import(ParticipantImport $importRecord, array $rows, ?User $admin = null, array $headerMap = []): array
    {
        $importRecord->update([
            'status' => ParticipantImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'error_rows' => [],
        ];

        DB::transaction(function () use ($importRecord, $rows, $admin, $headerMap, &$results) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $rowData = $this->mapRow($row, $headerMap);

                $validation = $this->validateRow($rowData, $rowNumber);
                if ($validation['invalid']) {
                    $results['errors']++;
                    $results['error_rows'][] = [
                        'row' => $rowNumber,
                        'data' => $row,
                        'errors' => $validation['messages'],
                    ];
                    continue;
                }

                $duplicate = $this->detectDuplicates($rowData);
                if ($duplicate['type'] === 'possible_duplicate' && $duplicate['existing_participant']) {
                    $results['duplicates']++;
                    continue;
                }

                $participantData = [
                    'first_name' => $rowData['first_name'],
                    'last_name' => $rowData['last_name'],
                    'contact' => $this->normalizeContact($rowData['contact'] ?? null),
                    'age' => $this->normalizeAge($rowData['age'] ?? null),
                    'unit' => $rowData['unit'] ?? null,
                    'stake_district' => $rowData['stake_district'] ?? null,
                    'shirt_size' => $rowData['shirt_size'] ?? null,
                    'status' => 'active',
                ];

                if (!isset($participantData['registration_number'])) {
                    $participantData['registration_number'] = app(RegistrationNumberService::class)->generate();
                }

                Participant::create($participantData);
                $results['imported']++;
            }
        });

        $importRecord->update([
            'total_rows' => count($rows),
            'imported_count' => $results['imported'],
            'updated_count' => $results['updated'],
            'skipped_count' => $results['skipped'],
            'duplicate_count' => $results['duplicates'],
            'error_count' => $results['errors'],
            'status' => $results['errors'] > 0 ? ParticipantImport::STATUS_COMPLETED_WITH_ERRORS : ParticipantImport::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        if ($admin) {
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => AuditLog::ACTION_PARTICIPANT_IMPORTED,
                'entity_type' => 'participant_import',
                'entity_id' => $importRecord->id,
                'description' => "Imported {$results['imported']} participants from {$importRecord->file_name}",
                'new_values' => $results,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $results;
    }

    public function getHeaderMap(): array
    {
        return $this->headerMap;
    }

    protected function mapRow(array $row, array $headerMap = []): array
    {
        if (empty($headerMap)) {
            $headerMap = $this->headerMap;
        }

        $result = [];
        foreach ($row as $key => $value) {
            if (isset($headerMap[$key])) {
                $result[$headerMap[$key]] = $value;
            }
        }

        return $result;
    }

    protected function validateRow(array $rowData, int $rowNumber): array
    {
        $messages = [];
        $invalid = false;

        if (empty(trim((string) ($rowData['first_name'] ?? '')))) {
            $messages[] = "Row {$rowNumber}: First name is required.";
            $invalid = true;
        }

        if (empty(trim((string) ($rowData['last_name'] ?? '')))) {
            $messages[] = "Row {$rowNumber}: Last name is required.";
            $invalid = true;
        }

        if (isset($rowData['age']) && $rowData['age'] !== '') {
            $age = is_numeric($rowData['age']) ? (int) $rowData['age'] : null;
            if ($age === null || $age < 0 || $age > 150) {
                $messages[] = "Row {$rowNumber}: Age must be between 0 and 150.";
                $invalid = true;
            }
        }

        return [
            'invalid' => $invalid,
            'messages' => $messages,
        ];
    }

    protected function normalizeContact(?string $contact): ?string
    {
        if ($contact === null || $contact === '') {
            return null;
        }

        $contact = trim((string) $contact);

        if (is_numeric($contact)) {
            $contact = (string) (int) $contact;
        }

        return $contact;
    }

    protected function normalizeAge($age): ?int
    {
        if ($age === null || $age === '') {
            return null;
        }

        if (is_numeric($age)) {
            return (int) $age;
        }

        return null;
    }
}
