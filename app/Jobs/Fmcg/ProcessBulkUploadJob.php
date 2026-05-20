<?php

namespace App\Jobs\Fmcg;

use App\Models\BulkUpload;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Fmcg\PricingEngine;
use App\Services\Fmcg\InventoryEngine;
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

    public function handle(PricingEngine $pricingEngine, InventoryEngine $inventoryEngine): void
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

            // 3. Create the Order shell (we will update its status and totals later)
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

            $totalRequestedQty = 0;
            $totalAllocatedQty = 0;
            $totalBackorderQty = 0;

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

                    // Call Inventory Engine for thread-safe allocation
                    $allocation = $inventoryEngine->allocate($product, $qty);
                    $allocatedQty = $allocation['allocated_qty'];
                    $backorderQty = $allocation['backorder_qty'];
                    $allocationStatus = $allocation['status'];

                    $totalRequestedQty += $qty;
                    $totalAllocatedQty += $allocatedQty;
                    $totalBackorderQty += $backorderQty;

                    $orderLines[] = [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'allocated_quantity' => $allocatedQty,
                        'backorder_quantity' => $backorderQty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'allocation_status' => $allocationStatus,
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

            // Check if backorder quantity exceeds 20% threshold
            if ($totalRequestedQty > 0) {
                $backorderRatio = $totalBackorderQty / $totalRequestedQty;
                if ($backorderRatio > 0.20) {
                    $allPolicyFlags[] = 'Backorder > 20%';
                }
            }

            // Determine order status (auto-approved vs pending manual review)
            $orderStatus = 'pending_review';
            if (count($allPolicyFlags) === 0) {
                // Auto-approved since no policy flags were triggered!
                $orderStatus = $order->determineFulfillmentStatus($totalAllocatedQty, $totalBackorderQty);
            }

            // Update order totals, policy flags, and final status
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal, // Tax/Shipping could be added here
                'policy_flags' => count($allPolicyFlags) > 0 ? array_values(array_unique($allPolicyFlags)) : null,
                'projected_margin' => $avgMargin,
                'status' => $orderStatus,
            ]);

            // Mark upload as processed
            $this->upload->update([
                'status' => BulkUpload::STATUS_PROCESSED,
                'finished_at' => now(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            // Mark invalid/failed if an error occurred during transaction
            $this->upload->update([
                'status' => BulkUpload::STATUS_INVALID,
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}
