<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\BulkUpload;
use App\Models\Customer;
use App\Models\Product;
use App\Jobs\Fmcg\ProcessBulkUploadJob;
use App\Services\Fmcg\InventoryEngine;
use App\Services\Fmcg\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function approver_can_approve_pending_review_order_and_audit_is_created()
    {
        // seed a user with approver role
        $approver = User::factory()->create([
            'role' => 'approver',
        ]);
        $this->actingAs($approver);

        // create a simple pending order via bulk upload that triggers review
        $customer = Customer::create([
            'name' => 'TestCo',
            'email' => 'test@example.com',
            'type' => 'silver',
            'phone' => '1112223333',
            'address' => '123 Test St',
        ]);

        $product = Product::create([
            'sku' => 'ITEM-LOWSTOCK',
            'name' => 'Low Stock Item',
            'unit_of_measure' => 'Piece',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 5.00,
            'stock' => 5,
            'is_active' => true,
        ]);

        $csv = "sku,quantity\nITEM-LOWSTOCK,20\n"; // will cause backorder >20%
        Storage::disk('local')->put('uploads/review.csv', $csv);

        $upload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => null,
            'file_name' => 'review.csv',
            'storage_path' => 'uploads/review.csv',
            'column_mapping' => ['sku' => 'sku', 'quantity' => 'quantity'],
            'status' => BulkUpload::STATUS_PROCESSING,
        ]);

        $job = new ProcessBulkUploadJob($upload);
        $job->handle(new PricingEngine(), new InventoryEngine());

        $order = Order::where('bulk_upload_id', $upload->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending_review', $order->status);

        // approve via route
        $response = $this->post(route('fmcg.approvals.approve', $order));
        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('allocated', $order->status);
        $this->assertEquals($approver->id, $order->approved_by);
        $this->assertNotNull($order->approved_at);

        // audit log entry exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order_approved',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'user_id' => $approver->id,
        ]);
    }

    /** @test */
    public function ops_user_cannot_approve_order_and_receives_forbidden()
    {
        $ops = User::factory()->create(['role' => 'ops']);
        $this->actingAs($ops);

        $order = Order::factory()->create(['status' => 'pending_review']);

        $response = $this->post(route('fmcg.approvals.approve', $order));
        $response->assertForbidden();
    }
}
