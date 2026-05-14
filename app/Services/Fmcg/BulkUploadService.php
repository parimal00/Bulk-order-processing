<?php

namespace App\Services\Fmcg;

use App\Models\BulkUpload;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BulkUploadService
{
    public function createUpload(UploadedFile $file, ?int $uploadedBy = null): BulkUpload
    {
        $customer = $this->resolveDefaultCustomer();

        $storagePath = $file->store('bulk-uploads');

        return BulkUpload::query()->create([
            'customer_id' => $customer->id,
            'uploaded_by' => $uploadedBy,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $storagePath,
            'file_hash' => hash_file('sha256', Storage::path($storagePath)),
            'file_type' => strtolower($file->getClientOriginalExtension() ?: 'csv'),
            'status' => BulkUpload::STATUS_UPLOADED,
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
        ]);
    }

    protected function resolveDefaultCustomer(): Customer
    {
        $defaultCustomerId = (int) env('BULK_UPLOAD_DEFAULT_CUSTOMER_ID', 1);

        return Customer::query()
            ->whereKey($defaultCustomerId)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
