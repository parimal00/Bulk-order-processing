<?php

namespace Tests\Feature;

use App\Models\BulkUpload;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test query on Products table.
     */
    public function test_query_active_products()
    {
        Product::factory()->create([
            'sku' => 'PROD-100',
            'name' => 'Active Widget',
            'is_active' => true,
            'moq' => 10,
        ]);

        Product::factory()->create([
            'sku' => 'PROD-200',
            'name' => 'Inactive Widget',
            'is_active' => false,
        ]);

        $activeProducts = Product::where('is_active', true)->get();

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('PROD-100', $activeProducts->first()->sku);
    }

    /**
     * Test query on BulkUploads table.
     */
    public function test_query_bulk_uploads_status()
    {
        $upload = BulkUpload::factory()->create([
            'status' => BulkUpload::STATUS_PROCESSED,
            'total_rows' => 50,
            'valid_rows' => 45,
            'invalid_rows' => 5,
        ]);

        $foundUpload = BulkUpload::where('status', BulkUpload::STATUS_PROCESSED)->first();

        $this->assertNotNull($foundUpload);
        $this->assertEquals(50, $foundUpload->total_rows);
    }

    /**
     * Test orders aggregation query (Week 3 performance optimized at-risk calculation).
     */
    public function test_query_at_risk_orders_aggregation()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $upload = BulkUpload::factory()->create([
            'customer_id' => $customer->id,
            'status' => BulkUpload::STATUS_PROCESSED,
        ]);

        // At-risk order (backorder ratio > 25%)
        $orderRisk = Order::factory()->create([
            'bulk_upload_id' => $upload->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
        ]);
        $orderRisk->lines()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 100,
            'allocated_quantity' => 60,
            'backorder_quantity' => 40, // 40% backorder ratio
            'unit_price' => 10.00,
            'line_total' => 1000.00,
            'allocation_status' => 'partial',
        ]);

        // Safe order (backorder ratio <= 25%)
        $orderSafe = Order::factory()->create([
            'bulk_upload_id' => $upload->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
        ]);
        $orderSafe->lines()->create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 100,
            'allocated_quantity' => 90,
            'backorder_quantity' => 10, // 10% backorder ratio
            'unit_price' => 10.00,
            'line_total' => 1000.00,
            'allocation_status' => 'partial',
        ]);

        // Perform aggregated query
        $riskUploadIds = Order::query()
            ->withSum('lines as total_qty', 'quantity')
            ->withSum('lines as total_backorder', 'backorder_quantity')
            ->whereNotNull('bulk_upload_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->get()
            ->filter(function ($order) {
                return $order->total_qty > 0 && ($order->total_backorder / $order->total_qty) > 0.25;
            })
            ->pluck('bulk_upload_id')
            ->unique()
            ->all();

        $this->assertCount(1, $riskUploadIds);
        $this->assertEquals($upload->id, $riskUploadIds[0]);
    }
}
