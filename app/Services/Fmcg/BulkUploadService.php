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

        if (!$storagePath) {
            throw new \RuntimeException('Failed to store uploaded file.');
        }

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

    public function saveMapping(BulkUpload $upload, array $mapping): void
    {
        $upload->update([
            'column_mapping' => $mapping,
            'status' => BulkUpload::STATUS_VALIDATING,
            'started_at' => now(),
        ]);

        // TODO: Dispatch validation job
    }

    protected function resolveDefaultCustomer(): Customer
    {
        $defaultCustomerId = (int) env('BULK_UPLOAD_DEFAULT_CUSTOMER_ID', 1);

        return Customer::query()
            ->whereKey($defaultCustomerId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getCsvMetadata(BulkUpload $upload): array
    {
        if (empty($upload->storage_path) || !Storage::exists($upload->storage_path)) {
            return [
                'headers' => [],
                'sampleData' => [],
            ];
        }

        $csv = \League\Csv\Reader::createFromPath(Storage::path($upload->storage_path), 'r');
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();

        $stmt = \League\Csv\Statement::create()->limit(3);
        $records = $stmt->process($csv);

        $sampleData = [];
        foreach ($records as $record) {
            $sampleData[] = $record;
        }

        return [
            'headers' => $headers,
            'sampleData' => $sampleData,
        ];
    }
}
