<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmcg\StoreBulkUploadRequest;
use App\Services\Fmcg\BulkUploadService;
use Illuminate\Http\RedirectResponse;
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


        $csv = Reader::createFromPath(Storage::path($upload->storage_path), 'r');

        $csv->setHeaderOffset(0); 
        $headers = $csv->getHeader();
        
        $stmt = Statement::create()->limit(3);
        $records = $stmt->process($csv);
        
        $sampleData = [];
        foreach ($records as $record) {
            $sampleData[] = $record;
        }

        return back()->with([
            'success' => "Upload {$upload->id} received.",
            'upload' => $upload,
            'headers' => $headers,
            'sampleData' => $sampleData,
        ]);
    }
}
