<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmcg\StoreBulkUploadRequest;
use App\Services\Fmcg\BulkUploadService;
use Illuminate\Http\RedirectResponse;

class BulkUploadController extends Controller
{
    public function store(StoreBulkUploadRequest $request, BulkUploadService $bulkUploadService): RedirectResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $upload = $bulkUploadService->createUpload($file, $request->user()?->id);

        return back()->with('success', "Upload {$upload->id} received.");
    }
}
