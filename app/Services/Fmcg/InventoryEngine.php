<?php

namespace App\Services\Fmcg;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryEngine
{
    /**
     * Allocate inventory for a requested product and quantity.
     * Deducts stock atomically from the database using a database transaction and pessimistic row locking.
     * 
     * @return array{allocated_qty: int, backorder_qty: int, status: string}
     */
    public function allocate(Product $product, int $requestedQty): array
    {
        return DB::transaction(function () use ($product, $requestedQty) {
            // Lock the product row for update to guarantee thread-safe stock checks and updates
            /** @var Product|null $freshProduct */
            $freshProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            if (!$freshProduct) {
                return [
                    'allocated_qty' => 0,
                    'backorder_qty' => $requestedQty,
                    'status' => 'backordered',
                ];
            }

            $currentStock = $freshProduct->stock;

            if ($currentStock >= $requestedQty) {
                // Full Fill
                $freshProduct->decrement('stock', $requestedQty);

                return [
                    'allocated_qty' => $requestedQty,
                    'backorder_qty' => 0,
                    'status' => 'allocated',
                ];
            } elseif ($currentStock > 0) {
                // Partial Fill
                $allocated = $currentStock;
                $backordered = $requestedQty - $currentStock;
                
                $freshProduct->update(['stock' => 0]);

                return [
                    'allocated_qty' => $allocated,
                    'backorder_qty' => $backordered,
                    'status' => 'partially_fulfilled',
                ];
            } else {
                return [
                    'allocated_qty' => 0,
                    'backorder_qty' => $requestedQty,
                    'status' => 'backordered',
                ];
            }
        });
    }

    /**
     * Release all allocated inventory back to product stocks (e.g. when an order is rejected).
     */
    public function releaseOrder(\App\Models\Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('lines');

            // 1. Map product IDs to their total allocated quantities (merges duplicates)
            $allocations = [];
            foreach ($order->lines as $line) {
                if ($line->allocated_quantity > 0) {
                    $allocations[$line->product_id] = ($allocations[$line->product_id] ?? 0) + $line->allocated_quantity;
                }
            }

            if (empty($allocations)) {
                return;
            }

            // 2. Deadlock Prevention: Sort the product IDs consistently
            $productIds = array_keys($allocations);
            sort($productIds);

            // 3. Bulk Lock: Fetch and lock all products in a single SQL query
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 4. Update stocks
            foreach ($allocations as $productId => $qty) {
                $product = $products->get($productId);
                if ($product) {
                    $product->increment('stock', $qty);
                }
            }
        });
    }
}
