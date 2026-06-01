<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmcg\ProcessMappingRequest;
use App\Http\Requests\Fmcg\StoreBulkUploadRequest;
use App\Jobs\Fmcg\ProcessBulkUploadJob;
use App\Models\BulkUpload;
use App\Services\Fmcg\BulkUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BulkUploadController extends Controller
{
    public function index(): Response
    {
        $uploads = BulkUpload::query()
            ->with(['customer', 'uploader'])
            ->latest('id')
            ->get()
            ->map(function (BulkUpload $upload): array {
                $totalRows = max(0, (int) $upload->total_rows);
                $validRows = max(0, (int) $upload->valid_rows);

                return [
                    'id' => "UPL-{$upload->id}",
                    'rawId' => $upload->id,
                    'customer' => $upload->customer?->name ?? 'Unknown',
                    'source' => strtoupper($upload->file_type ?? 'CSV'),
                    'rows' => $totalRows,
                    'validPercent' => $totalRows > 0 ? (int) round(($validRows / $totalRows) * 100) : 0,
                    'status' => $this->mapUploadStatus($upload->status),
                    'createdAt' => $upload->created_at ? $upload->created_at->format('M d, Y H:i') : 'N/A',
                    'owner' => $upload->uploader?->name ?? 'System',
                ];
            })
            ->values()
            ->all();

        return Inertia::render('fmcg/uploads/index', [
            'uploads' => $uploads,
        ]);
    }

    private function mapUploadStatus(string $status): string
    {
        return match ($status) {
            BulkUpload::STATUS_VALID => 'ready',
            BulkUpload::STATUS_FAILED_ROWS, BulkUpload::STATUS_INVALID => 'failed',
            BulkUpload::STATUS_PROCESSED => 'completed',
            default => $status,
        };
    }

    public function processing(): Response
    {
        $now = now();
        $jobs = BulkUpload::query()
            ->latest('updated_at')
            ->get()
            ->map(function (BulkUpload $upload) use ($now): array {
                $rowsProcessed = (int) $upload->valid_rows + (int) $upload->invalid_rows;
                $totalRows = max(0, (int) $upload->total_rows);

                $progress = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 0,
                    BulkUpload::STATUS_VALIDATING => $totalRows > 0 ? min(95, (int) round(($rowsProcessed / $totalRows) * 100)) : 20,
                    BulkUpload::STATUS_PROCESSING => 70,
                    BulkUpload::STATUS_PROCESSED => 100,
                    BulkUpload::STATUS_VALID => 100,
                    BulkUpload::STATUS_FAILED_ROWS, BulkUpload::STATUS_INVALID => 100,
                    default => 0,
                };

                $step = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 'Awaiting Mapping',
                    BulkUpload::STATUS_VALIDATING => 'Validation Pipeline',
                    BulkUpload::STATUS_VALID => 'Validation Passed',
                    BulkUpload::STATUS_FAILED_ROWS, BulkUpload::STATUS_INVALID => 'Validation Failed',
                    BulkUpload::STATUS_PROCESSING => 'Pricing and Allocation',
                    BulkUpload::STATUS_PROCESSED => 'Ingestion Complete',
                    default => 'Queued',
                };

                $status = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 'queued',
                    BulkUpload::STATUS_VALIDATING, BulkUpload::STATUS_PROCESSING => 'running',
                    BulkUpload::STATUS_PROCESSED, BulkUpload::STATUS_VALID => 'completed',
                    BulkUpload::STATUS_FAILED_ROWS, BulkUpload::STATUS_INVALID => 'failed',
                    default => 'queued',
                };

                // format duration
                $seconds = $upload->created_at ? (float) $upload->created_at->diffInSeconds($now) : 0;
                $secondsInt = (int) round($seconds);
                $minutes = intdiv($secondsInt, 60);
                $remainingSeconds = $secondsInt % 60;
                $elapsed = sprintf('%dm %02ds', $minutes, $remainingSeconds);

                return [
                    'id' => "JOB-UPL-{$upload->id}",
                    'rawId' => $upload->id,
                    'uploadId' => "UPL-{$upload->id}",
                    'step' => $step,
                    'progress' => max(0, min(100, $progress)),
                    'status' => $status,
                    'elapsed' => $elapsed,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('fmcg/processing', [
            'jobs' => $jobs,
        ]);
    }

    public function store(StoreBulkUploadRequest $request, BulkUploadService $bulkUploadService): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $upload = $bulkUploadService->createUpload($file, $request->user()?->id);

        return redirect()->route('fmcg.uploads.new', ['upload' => $upload->id])
            ->with('success', "Upload {$upload->id} received.");
    }

    public function processMapping(ProcessMappingRequest $request, BulkUpload $upload, BulkUploadService $bulkUploadService): RedirectResponse
    {
        $bulkUploadService->saveMapping($upload, $request->validated('mapping'));

        return redirect()->route('fmcg.uploads.validation', ['upload' => $upload->id])->with('success', 'Mapping saved. Validation started.');
    }

    public function validation(BulkUpload $upload): Response
    {
        $errors = $upload->validationErrors()->paginate(50);

        return Inertia::render('fmcg/uploads/validation', [
            'upload' => $upload,
            'errors' => $errors,
        ]);
    }

    public function process(BulkUpload $upload): RedirectResponse
    {
        if (! in_array($upload->status, [BulkUpload::STATUS_VALID, BulkUpload::STATUS_INVALID, BulkUpload::STATUS_FAILED_ROWS])) {
            return back()->with('error', 'Cannot process an upload in this status.');
        }

        $upload->update(['status' => BulkUpload::STATUS_PROCESSING]);

        ProcessBulkUploadJob::dispatch($upload);

        $redirectRoute = auth()->user()?->hasRole(['approver', 'admin'])
            ? 'fmcg.orders.index'
            : 'fmcg.uploads.index';

        return redirect()->route($redirectRoute)->with('success', 'Processing started. Orders will appear here shortly.');
    }

    /**
     * Download a CSV containing only the rows that failed validation,
     * using the original CSV headers and appending an "Error" column.
     */
    public function downloadFailedRows(BulkUpload $upload)
    {
        // Read the original CSV headers from the uploaded file
        $csv = \League\Csv\Reader::createFromPath(
            Storage::path($upload->storage_path), 'r'
        );
        $csv->setHeaderOffset(0);
        $originalHeaders = $csv->getHeader();

        // Get failed rows grouped by row_number so we can merge
        // multiple errors for the same row into one "Error" string
        $failedRows = $upload->failedRows()
            ->orderBy('row_number')
            ->get()
            ->groupBy('row_number');

        $callback = function () use ($originalHeaders, $failedRows) {
            $handle = fopen('php://output', 'w');

            // Write header: original columns + "Error"
            fputcsv($handle, array_merge($originalHeaders, ['Error']));

            foreach ($failedRows as $rowNumber => $errors) {
                // raw_data is the original CSV row stored as JSON
                $rawData = $errors->first()->raw_data ?? [];

                // Rebuild the row in the same column order as the original CSV
                $csvRow = [];
                foreach ($originalHeaders as $header) {
                    $csvRow[] = $rawData[$header] ?? '';
                }

                // Concatenate all error messages for this row
                $errorMessages = $errors
                    ->pluck('error_message')
                    ->unique()
                    ->implode(' | ');

                $csvRow[] = $errorMessages;

                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        };

        $fileName = "failed_rows_upload_{$upload->id}.csv";

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function deleteFailedRows(BulkUpload $upload)
    {
        // $upload->failedRows()->delete();
        // $upload->validationErrors()->delete();
        // $upload->update(['status' => BulkUpload::STATUS_PENDING]);
        // return redirect()->route('fmcg.orders.index')->with('success', 'Processing started. Orders will appear here shortly.');
    }
}
