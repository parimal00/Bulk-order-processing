<?php

namespace App\Http\Controllers;

use App\Jobs\Fmcg\ValidateBulkUploadJob;
use App\Models\BulkUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkUploadController extends Controller
{
    /**
     * Stream the original CSV file so the user can download and fix it.
     */
    public function downloadOriginal(BulkUpload $upload): BinaryFileResponse
    {
        $path = Storage::path($upload->storage_path);
        return response()->download($path, $upload->original_filename);
    }

    /**
     * Accept a corrected CSV, create a brand‑new BulkUpload record and fire validation.
     * This implements the classic "fix‑and‑re‑upload" workflow.
     */
    public function replace(Request $request, BulkUpload $upload)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv');
        // Store under a unique name to avoid collisions
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $storagePath = $file->storeAs('bulk-uploads', $filename);

        // Compute a hash for integrity checks (optional but useful)
        $hash = md5_file(Storage::path($storagePath));

        // Clone the original upload's meta data (customer, mapping, etc.)
        $newUpload = BulkUpload::create([
            'customer_id'       => $upload->customer_id,
            'uploaded_by'       => auth()->id(),
            'original_filename' => $file->getClientOriginalName(),
            'storage_path'      => $storagePath,
            'file_hash'          => $hash,
            'file_type'         => $file->getMimeType(),
            'status'             => BulkUpload::STATUS_UPLOADED,
            'column_mapping'    => $upload->column_mapping,
            'meta'              => $upload->meta,
            'started_at'        => now(),
        ]);

        // Kick off the validation job for the fresh upload
        ValidateBulkUploadJob::dispatch($newUpload);

        // Redirect back to the uploads list with a flash message
        return redirect()->route('fmcg.uploads.index')
            ->with('success', 'Corrected file uploaded and validation started.');
    }
}
