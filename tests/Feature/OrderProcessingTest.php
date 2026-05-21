<?php

namespace Tests\Feature;

use App\Jobs\Fmcg\ProcessBulkUploadJob;
use App\Models\BulkUpload;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\Fmcg\InventoryEngine;
use App\Services\Fmcg\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_standard_order_processing_with_sufficient_stock_auto_approves(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retail Group',
            'email' => 'metro@example.com',
            'type' => 'gold',
            'phone' => '1234567890',
            'address' => '123 Retail Lane',
        ]);

        $product1 = Product::create([
            'sku' => 'RICE-BASMATI-5KG',
            'name' => 'Basmati Rice 5KG',
            'unit_of_measure' => 'Bag',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 15.50,
            'stock' => 500,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'sku' => 'OIL-SUNFLOWER-1L',
            'name' => 'Sunflower Oil 1L',
            'unit_of_measure' => 'Bottle',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 4.50,
            'stock' => 200,
            'is_active' => true,
        ]);

        // Create a CSV content and store it
        $csvContent = "sku,quantity\nRICE-BASMATI-5KG,10\nOIL-SUNFLOWER-1L,20\n";
        Storage::disk('local')->put('uploads/test_order_1.csv', $csvContent);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'test_order_1.csv',
            'storage_path' => 'uploads/test_order_1.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        // Run the processing job
        $job = new ProcessBulkUploadJob($upload);
        $job->handle(new PricingEngine(), new InventoryEngine());

        // Verify order is created and auto-approved
        $order = Order::where('bulk_upload_id', $upload->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('allocated', $order->status); // Auto-approved and fully allocated!
        $this->assertEquals(0, count($order->policy_flags ?? []));

        // Check stock reductions
        $this->assertEquals(490, $product1->fresh()->stock);
        $this->assertEquals(180, $product2->fresh()->stock);

        // Verify lines
        $this->assertEquals(2, $order->lines()->count());
        $this->assertEquals(10, $order->lines()->where('sku', 'RICE-BASMATI-5KG')->first()->allocated_quantity);
    }

    public function test_order_processing_with_high_backorder_triggers_policy_flag_and_routes_to_review(): void
    {
        $customer = Customer::create([
            'name' => 'EverFresh Supermart',
            'email' => 'everfresh@example.com',
            'type' => 'silver',
            'phone' => '0987654321',
            'address' => '456 Fresh Blvd',
        ]);

        $product = Product::create([
            'sku' => 'SOAP-LEMON-100G',
            'name' => 'Lemon Soap 100G',
            'unit_of_measure' => 'Piece',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 1.20,
            'stock' => 10, // low stock
            'is_active' => true,
        ]);

        // Create CSV requesting more than available stock
        $csvContent = "sku,quantity\nSOAP-LEMON-100G,100\n"; // 90% backordered
        Storage::disk('local')->put('uploads/test_order_2.csv', $csvContent);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'test_order_2.csv',
            'storage_path' => 'uploads/test_order_2.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        $job = new ProcessBulkUploadJob($upload);
        $job->handle(new PricingEngine(), new InventoryEngine());

        $order = Order::where('bulk_upload_id', $upload->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending_review', $order->status); // Routed to manual approval queue
        $this->assertContains('Backorder > 20%', $order->policy_flags);

        // Check stock is reserved/fully depleted
        $this->assertEquals(0, $product->fresh()->stock);

        // Check line allocations
        $line = $order->lines()->first();
        $this->assertEquals(10, $line->allocated_quantity);
        $this->assertEquals(90, $line->backorder_quantity);
        $this->assertEquals('partially_fulfilled', $line->allocation_status);
    }

    public function test_job_is_idempotent_on_retry(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retail Group',
            'email' => 'metro@example.com',
            'type' => 'gold',
            'phone' => '1234567890',
            'address' => '123 Retail Lane',
        ]);

        $product = Product::create([
            'sku' => 'RICE-BASMATI-5KG',
            'name' => 'Basmati Rice 5KG',
            'unit_of_measure' => 'Bag',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 15.50,
            'stock' => 100,
            'is_active' => true,
        ]);

        $csvContent = "sku,quantity\nRICE-BASMATI-5KG,10\n";
        Storage::disk('local')->put('uploads/test_idempotency.csv', $csvContent);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'test_idempotency.csv',
            'storage_path' => 'uploads/test_idempotency.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        $pricingEngine = new PricingEngine();
        $inventoryEngine = new InventoryEngine();

        // 1. First run of the job
        $job = new ProcessBulkUploadJob($upload);
        $job->handle($pricingEngine, $inventoryEngine);

        // Verify order created and stock decremented
        $this->assertEquals(1, Order::where('bulk_upload_id', $upload->id)->count());
        $this->assertEquals(90, $product->fresh()->stock);
        $this->assertEquals(BulkUpload::STATUS_PROCESSED, $upload->fresh()->status);

        // 2. Simulate Retry: set status back to processing and run the job again
        $upload->update(['status' => BulkUpload::STATUS_PROCESSING]);
        $job2 = new ProcessBulkUploadJob($upload);
        $job2->handle($pricingEngine, $inventoryEngine);

        // Verify NO duplicate orders and NO extra stock deductions
        $this->assertEquals(1, Order::where('bulk_upload_id', $upload->id)->count());
        $this->assertEquals(90, $product->fresh()->stock);
        $this->assertEquals(BulkUpload::STATUS_PROCESSED, $upload->fresh()->status);
    }

    public function test_concurrent_job_execution_prevents_duplicate_orders(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retail Group',
            'email' => 'metro@example.com',
            'type' => 'gold',
            'phone' => '1234567890',
            'address' => '123 Retail Lane',
        ]);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'test_concurrent.csv',
            'storage_path' => 'uploads/test_concurrent.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        // Manually acquire the lock to simulate another worker holding it
        $lock = \Illuminate\Support\Facades\Cache::lock("process_upload_{$upload->id}", 60);
        $lock->get();

        // Mock the queue job to assert that release is called
        $mockJob = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $mockJob->expects($this->once())
            ->method('release')
            ->with(5);

        $job = new ProcessBulkUploadJob($upload);
        $job->setJob($mockJob);

        // Run handle - it should hit the lock, release itself, and return early
        $job->handle(new PricingEngine(), new InventoryEngine());

        // Release the lock for clean up
        $lock->release();
    }

    public function test_job_handles_permanent_failure_after_exhausting_retries(): void
    {
        $customer = Customer::create([
            'name' => 'Metro Retail Group',
            'email' => 'metro@example.com',
            'type' => 'gold',
            'phone' => '1234567890',
            'address' => '123 Retail Lane',
        ]);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'test_failed.csv',
            'storage_path' => 'uploads/test_failed.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        $job = new ProcessBulkUploadJob($upload);
        $job->failed(new \Exception('Database crash on processing row'));

        // Verify upload is marked as invalid/failed and ValidationError logged
        $this->assertEquals(BulkUpload::STATUS_INVALID, $upload->fresh()->status);
        $this->assertNotNull($upload->fresh()->finished_at);

        $error = $upload->validationErrors()->first();
        $this->assertNotNull($error);
        $this->assertEquals('SYSTEM_ERROR', $error->error_code);
        $this->assertStringContainsString('Processing failed: Database crash on processing row', $error->error_message);
    }
}
