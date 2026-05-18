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
        if (! in_array($upload->status, [BulkUpload::STATUS_VALID, BulkUpload::STATUS_INVALID])) {
            return back()->with('error', 'Cannot process an upload in this status.');
        }

        $upload->update(['status' => BulkUpload::STATUS_PROCESSING]);

        ProcessBulkUploadJob::dispatch($upload);

        return redirect()->route('fmcg.approvals')->with('success', 'Processing started. Orders will appear here shortly.');
    }
}
