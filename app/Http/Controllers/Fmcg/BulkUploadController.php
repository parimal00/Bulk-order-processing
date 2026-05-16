<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmcg\ProcessMappingRequest;
use App\Http\Requests\Fmcg\StoreBulkUploadRequest;
use App\Models\BulkUpload;
use App\Services\Fmcg\BulkUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class BulkUploadController extends Controller
{
    public function store(StoreBulkUploadRequest $request, BulkUploadService $bulkUploadService): RedirectResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $upload = $bulkUploadService->createUpload($file, $request->user()?->id);

        return redirect()->route('fmcg.uploads.new', ['upload' => $upload->id])
            ->with('success', "Upload {$upload->id} received.");
    }

    public function processMapping(ProcessMappingRequest $request, BulkUpload $upload, BulkUploadService $bulkUploadService): RedirectResponse
    {
        $bulkUploadService->saveMapping($upload, $request->validated('mapping'));

        return redirect()->route('fmcg.uploads.validation')->with('success', 'Mapping saved. Validation started.');
    }
}
