<?php

namespace App\Jobs\Fmcg;

use App\Models\BulkUpload;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Fmcg\PricingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ProcessBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(public BulkUpload $upload) {}

    public function handle(PricingEngine $pricingEngine): void
    {
        // Only process if status is processing to avoid double runs
        if ($this->upload->status !== BulkUpload::STATUS_PROCESSING) {
            return;
        }

        // Idempotency: Check if an order already exists for this upload
        if (Order::where('bulk_upload_id', $this->upload->id)->exists()) {
            $this->upload->update(['status' => BulkUpload::STATUS_PROCESSED]);

            return;
        }

        try {
            DB::beginTransaction();

            // 1. Fetch invalid row numbers to skip them
            $invalidRowNumbers = $this->upload->validationErrors()->pluck('row_number')->flip()->toArray();

            // 2. Read the CSV
            $csv = Reader::createFromPath(Storage::path($this->upload->storage_path), 'r');
            $csv->setHeaderOffset(0);

            $mapping = $this->upload->column_mapping;
            $skuCol = $mapping['sku'] ?? null;
            $qtyCol = $mapping['quantity'] ?? null;

            if (! $skuCol || ! $qtyCol) {
                throw new \Exception('Missing required column mapping for sku or quantity.');
            }

            // 3. Create the Order shell
            $order = Order::create([
                'bulk_upload_id' => $this->upload->id,
                'customer_id' => $this->upload->customer_id,
                'created_by' => $this->upload->uploaded_by,
                'order_number' => 'ORD-'.strtoupper(uniqid()), // Simple unique ID for now
                'status' => 'pending_review',
                'currency' => 'USD',
                'subtotal' => 0,
                'total' => 0,
                'placed_at' => now(),
            ]);

            // Pre-fetch products to avoid N+1 queries
            $records = $csv->getRecords();
            $fileSkus = [];
            foreach ($records as $index => $record) {
                if (! isset($invalidRowNumbers[$index])) {
                    $sku = trim($record[$skuCol] ?? '');
                    if ($sku !== '') {
                        $fileSkus[] = $sku;
                    }
                }
            }

            $activeProducts = Product::whereIn('sku', array_unique($fileSkus))
                ->get()
                ->keyBy('sku');

            $orderLines = [];
            $subtotal = 0;
            $customer = $this->upload->customer;
            $allPolicyFlags = [];
            $margins = [];

            // Re-read CSV to build order lines
            $csv = Reader::createFromPath(Storage::path($this->upload->storage_path), 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            foreach ($records as $index => $record) {
                // Skip invalid rows
                if (isset($invalidRowNumbers[$index])) {
                    continue;
                }

                $sku = trim($record[$skuCol] ?? '');
                $qtyRaw = trim($record[$qtyCol] ?? '');
                $qty = (int) $qtyRaw;

                $product = $activeProducts->get($sku);

                if ($product && $qty > 0) {
                    // Call Pricing Engine
                    $pricing = $pricingEngine->calculate($customer, $product, $qty);
                    $unitPrice = $pricing['unit_price'];
                    $lineTotal = $unitPrice * $qty;

                    if (!empty($pricing['flags'])) {
                        $allPolicyFlags = array_merge($allPolicyFlags, $pricing['flags']);
                    }
                    $margins[] = (int) str_replace('%', '', $pricing['margin']);

                    $orderLines[] = [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'allocation_status' => 'pending', // Will be replaced by InventoryEngine later
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $subtotal += $lineTotal;
                }
            }

            // Bulk insert order lines
            if (count($orderLines) > 0) {
                $chunkedLines = array_chunk($orderLines, 500);
                foreach ($chunkedLines as $chunk) {
                    OrderLine::insert($chunk);
                }
            }

            // Calculate average margin
            $defaultMargin = config('fmcg.pricing.default_margin', 22);
            $avgMargin = count($margins) > 0 ? round(array_sum($margins) / count($margins)) . '%' : $defaultMargin . '%';

            // Update order totals and policy flags
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal, // Tax/Shipping could be added here
                'policy_flags' => count($allPolicyFlags) > 0 ? array_values(array_unique($allPolicyFlags)) : null,
                'projected_margin' => $avgMargin,
            ]);

            // Mark upload as processed
            $this->upload->update([
                'status' => BulkUpload::STATUS_PROCESSED,
                'finished_at' => now(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            // We can add a processing_failed status later if needed, but for now just mark invalid
            $this->upload->update([
                'status' => BulkUpload::STATUS_INVALID,
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}
