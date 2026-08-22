<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantImport;
use App\Services\Import\ParticipantImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportController extends Controller
{
    public function __construct(
        protected ParticipantImportService $importService
    ) {}

    public function index()
    {
        $imports = ParticipantImport::with('uploader')
            ->latest()
            ->paginate(20);

        return view('admin.import.index', compact('imports'));
    }

    public function create()
    {
        return view('admin.import.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $file = $request->file('file');
        $errors = $this->importService->validateFile($file);

        if (!empty($errors)) {
            return back()->withErrors(['file' => $errors])->withInput();
        }

        $preview = $this->importService->readPreview($file);

        if (!$preview['valid_headers']) {
            return back()->withErrors([
                'file' => 'Missing required columns: '.implode(', ', $preview['missing_headers']),
            ])->withInput();
        }

        $path = $file->store('imports/temp');

        return view('admin.import.preview', [
            'preview' => $preview,
            'tempPath' => $path,
            'fileName' => $file->getClientOriginalName(),
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'temp_path' => ['required', 'string'],
            'file_name' => ['required', 'string'],
        ]);

        $importRecord = new ParticipantImport([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'file_name' => $request->input('file_name'),
            'uploaded_by' => Auth::id(),
            'status' => ParticipantImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
        $importRecord->save();

        $filePath = storage_path('app/'.$request->input('temp_path'));
        if (!file_exists($filePath)) {
            $importRecord->update([
                'status' => ParticipantImport::STATUS_FAILED,
                'completed_at' => now(),
            ]);

            return redirect()->route('admin.import')->withErrors(['file' => 'Temporary file not found.']);
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $allRows = $sheet->toArray(null, true, true, true);

        $headerRow = array_shift($allRows);
        $headerValues = array_values($headerRow);
        $normalization = $this->importService->normalizeHeaders($headerValues);
        $headerMap = [];
        $columnIndex = 0;
        foreach ($headerRow as $columnLetter => $headerValue) {
            $clean = strtolower(trim((string) $headerValue));
            $clean = preg_replace('/[^a-z0-9]/', '', $clean) ?: $clean;
            $mapped = $this->importService->getHeaderMap()[$clean] ?? null;
            if ($mapped) {
                $headerMap[$columnLetter] = $mapped;
            }
            $columnIndex++;
        }

        $rows = array_values($allRows);

        $results = $this->importService->import($importRecord, $rows, Auth::user(), $headerMap);

        Storage::delete($request->input('temp_path'));

        return view('admin.import.results', [
            'import' => $importRecord,
            'results' => $results,
        ]);
    }

    public function show(ParticipantImport $import)
    {
        $import->load('uploader');

        return view('admin.import.show', compact('import'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['FName', 'Lname', 'contact', 'age', 'Unit', 'stake/district', 'shirt size'];
        $sheet->fromArray($headers, null, 'A1');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'summit_template_').'.xlsx';
        $writer->save($tempFile);

        return response()->download($tempFile, 'summit_participant_template.xlsx')->deleteFileAfterSend();
    }
}
