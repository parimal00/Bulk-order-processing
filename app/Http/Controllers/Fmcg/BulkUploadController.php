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
