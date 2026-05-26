<?php

namespace Tests\Feature;

use App\Jobs\Fmcg\SendOrderToIntegrationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderLine;
use App\Services\Fmcg\OutboundIntegrationStub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OutboundIntegrationStubTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_order_queues_outbound_integration_job(): void
    {
        Queue::fake();

        $approver = User::factory()->create(['role' => 'approver']);
        $customer = Customer::factory()->create([
            'name' => 'EverFresh Supermart',
            'tier' => 'silver',
            'email' => 'everfresh@example.com',
            'phone' => '1234567890',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-APPROVE-1001',
            'status' => Order::STATUS_PENDING_REVIEW,
            'currency' => 'USD',
            'subtotal' => 100,
            'total' => 100,
            'placed_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('fmcg.approvals.approve', $order))
            ->assertRedirect();

        Queue::assertPushed(SendOrderToIntegrationJob::class, function (SendOrderToIntegrationJob $job) use ($order) {
            return $job->orderId === $order->id;
        });
    }

    public function test_outbound_integration_job_creates_sent_sync_record(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Metro Retail Group',
            'tier' => 'gold',
            'email' => 'metro@example.com',
            'phone' => '1234567890',
        ]);

        $product = Product::create([
            'sku' => 'SKU-ERP-100',
            'name' => 'Test Product',
            'unit_of_measure' => 'Case',
            'moq' => 1,
            'pack_size' => 1,
            'base_price' => 25,
            'stock' => 100,
            'is_active' => true,
        ]);

        $order = $this->createSyncableOrder($customer->id);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 2,
            'allocated_quantity' => 2,
            'backorder_quantity' => 0,
            'unit_price' => 25,
            'line_total' => 50,
            'allocation_status' => 'allocated',
        ]);

        $job = new SendOrderToIntegrationJob($order->id);
        $job->handle(new OutboundIntegrationStub());

        $sync = OrderIntegration::where('order_id', $order->id)->first();

        $this->assertNotNull($sync);
        $this->assertSame(OrderIntegration::STATUS_SENT, $sync->status);
        $this->assertSame('ERP-'.$order->order_number, $sync->external_reference);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'integration_sync_sent',
            'entity_type' => OrderIntegration::class,
            'entity_id' => $sync->id,
        ]);
    }

    public function test_webhook_callback_marks_sync_as_acknowledged(): void
    {
        config(['services.erp_stub.webhook_token' => 'test-token']);

        $customer = Customer::factory()->create([
            'name' => 'NexMart Wholesale',
            'tier' => 'platinum',
            'email' => 'nexmart@example.com',
            'phone' => '1234567890',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-CALLBACK-2001',
            'status' => 'allocated',
            'currency' => 'USD',
            'subtotal' => 200,
            'total' => 200,
            'placed_at' => now(),
        ]);

        $sync = OrderIntegration::create([
            'order_id' => $order->id,
            'provider' => 'erp_stub',
            'status' => OrderIntegration::STATUS_SENT,
            'external_status' => 'received',
            'external_reference' => 'ERP-'.$order->order_number,
            'attempt_count' => 1,
            'sent_at' => now(),
        ]);

        $this->postJson(
            route('integrations.webhooks.order-sync'),
            [
                'provider' => 'erp_stub',
                'external_reference' => $sync->external_reference,
                'status' => 'acknowledged',
                'message' => 'Processed by external system.',
            ],
            ['X-Integration-Token' => 'test-token']
        )->assertOk();

        $this->assertDatabaseHas('order_integrations', [
            'id' => $sync->id,
            'status' => OrderIntegration::STATUS_ACKNOWLEDGED,
            'external_status' => 'acknowledged',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'integration_callback_received',
            'entity_type' => OrderIntegration::class,
            'entity_id' => $sync->id,
        ]);
    }

    private function createSyncableOrder(int $customerId): Order
    {
        do {
            $order = Order::create([
                'customer_id' => $customerId,
                'order_number' => 'ORD-SYNC-'.strtoupper((string) str()->random(8)),
                'status' => 'allocated',
                'currency' => 'USD',
                'subtotal' => 50,
                'total' => 50,
                'placed_at' => now(),
            ]);
        } while ($order->id % 5 === 0);

        return $order;
    }
}
