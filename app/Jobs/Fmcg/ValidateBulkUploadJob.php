<?php

namespace App\Jobs\Fmcg;

use App\Models\BulkUpload;
use App\Models\Product;
use App\Models\ValidationError;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ValidateBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(public BulkUpload $upload) {}

    public function handle(): void
    {
        if ($this->upload->status !== BulkUpload::STATUS_VALIDATING) {
            return;
        }

        try {
            $csv = Reader::createFromPath(Storage::path($this->upload->storage_path), 'r');
            $csv->setHeaderOffset(0);

            $mapping = $this->upload->column_mapping;
            $skuCol = $mapping['sku'] ?? null;
            $qtyCol = $mapping['quantity'] ?? null;

            if (! $skuCol || ! $qtyCol) {
                throw new \Exception('Missing required column mapping for sku or quantity.');
            }

            $records = $csv->getRecords();

            $totalRows = 0;
            $validRows = 0;
            $invalidRows = 0;

            $errorsToInsert = [];

            // Collect all unique SKUs in the file for faster validation
            $fileSkus = [];
            foreach ($records as $record) {
                $sku = trim($record[$skuCol] ?? '');
                if ($sku !== '') {
                    $fileSkus[] = $sku;
                }
            }

            // Fetch all active products matching the SKUs from DB
            $activeProducts = Product::whereIn('sku', array_unique($fileSkus))
                ->where('is_active', true)
                ->get()
                ->keyBy('sku');

            $seenSkus = []; // For duplicate detection

            $csv = Reader::createFromPath(Storage::path($this->upload->storage_path), 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            foreach ($records as $index => $record) {
                $rowNumber = $index;
                $totalRows++;

                $sku = trim($record[$skuCol] ?? '');
                $qtyRaw = trim($record[$qtyCol] ?? '');

                $rowErrors = [];

                // Validate SKU
                if ($sku === '') {
                    $rowErrors[] = [
                        'column_name' => $skuCol,
                        'error_code' => 'SKU_REQUIRED',
                        'error_message' => 'SKU is required.',
                        'raw_value' => $sku,
                    ];
                } else {
                    if (isset($seenSkus[$sku])) {
                        $rowErrors[] = [
                            'column_name' => $skuCol,
                            'error_code' => 'SKU_DUPLICATE',
                            'error_message' => 'Duplicate SKU found within the same upload.',
                            'raw_value' => $sku,
                        ];
                    } else {
                        $seenSkus[$sku] = true;

                        if (! $activeProducts->has($sku)) {
                            $rowErrors[] = [
                                'column_name' => $skuCol,
                                'error_code' => 'SKU_NOT_FOUND',
                                'error_message' => 'SKU does not exist or is inactive.',
                                'raw_value' => $sku,
                            ];
                        }
                    }
                }

                // Validate Quantity
                if ($qtyRaw === '') {
                    $rowErrors[] = [
                        'column_name' => $qtyCol,
                        'error_code' => 'QTY_REQUIRED',
                        'error_message' => 'Quantity is required.',
                        'raw_value' => $qtyRaw,
                    ];
                } elseif (! is_numeric($qtyRaw) || (int) $qtyRaw <= 0) {
                    $rowErrors[] = [
                        'column_name' => $qtyCol,
                        'error_code' => 'QTY_INVALID',
                        'error_message' => 'Quantity must be a positive integer.',
                        'raw_value' => $qtyRaw,
                    ];
                } else {
                    $qty = (int) $qtyRaw;
                    if ($activeProducts->has($sku)) {
                        $moq = $activeProducts->get($sku)->moq ?? 1;
                        if ($qty < $moq) {
                            $rowErrors[] = [
                                'column_name' => $qtyCol,
                                'error_code' => 'QTY_BELOW_MOQ',
                                'error_message' => "Quantity ($qty) is below the minimum order quantity ($moq).",
                                'raw_value' => $qtyRaw,
                            ];
                        }
                    }
                }

                if (count($rowErrors) > 0) {
                    $invalidRows++;
                    foreach ($rowErrors as $error) {
                        $errorsToInsert[] = [
                            'bulk_upload_id' => $this->upload->id,
                            'row_number' => $rowNumber,
                            'column_name' => $error['column_name'],
                            'error_code' => $error['error_code'],
                            'error_message' => $error['error_message'],
                            'raw_value' => $error['raw_value'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                } else {
                    $validRows++;
                }

                // Chunk inserts
                if (count($errorsToInsert) >= 500) {
                    ValidationError::insert($errorsToInsert);
                    $errorsToInsert = [];
                }
            }

            if (count($errorsToInsert) > 0) {
                ValidationError::insert($errorsToInsert);
            }

            $this->upload->update([
                'status' => $invalidRows > 0 ? BulkUpload::STATUS_INVALID : BulkUpload::STATUS_VALID,
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'finished_at' => now(),
            ]);

        } catch (\Exception $e) {
            $this->upload->update([
                'status' => BulkUpload::STATUS_INVALID,
                'finished_at' => now(),
            ]);

            ValidationError::create([
                'bulk_upload_id' => $this->upload->id,
                'row_number' => 0,
                'column_name' => null,
                'error_code' => 'SYSTEM_ERROR',
                'error_message' => 'An error occurred processing the file: '.$e->getMessage(),
            ]);
        }
    }
}
