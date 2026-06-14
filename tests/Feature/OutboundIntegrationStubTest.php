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
use Illuminate\Support\Facades\Http;
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

        Http::fake([
            '*/integrations/erp-stub/orders' => Http::response([
                'accepted' => true,
                'external_reference' => 'ERP-'.$order->order_number,
                'external_status' => 'received',
                'message' => 'Order accepted by ERP stub.',
            ], 200),
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

    public function test_outbound_integration_retries_on_transient_failures(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createSyncableOrder($customer->id);

        Http::fake([
            '*/integrations/erp-stub/orders' => Http::sequence()
                ->response('Transient Error', 503)
                ->response('Transient Error', 503)
                ->response([
                    'accepted' => true,
                    'external_reference' => 'ERP-'.$order->order_number,
                    'external_status' => 'received',
                    'message' => 'Order accepted by ERP stub.',
                ], 200),
        ]);

        $job = new SendOrderToIntegrationJob($order->id);
        $job->handle(new OutboundIntegrationStub());

        Http::assertSentCount(3);

        $sync = OrderIntegration::where('order_id', $order->id)->first();
        $this->assertNotNull($sync);
        $this->assertSame(OrderIntegration::STATUS_SENT, $sync->status);
        $this->assertSame('ERP-'.$order->order_number, $sync->external_reference);
    }

    public function test_outbound_integration_fails_permanently_after_all_retries(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createSyncableOrder($customer->id);

        Http::fake([
            '*/integrations/erp-stub/orders' => Http::response('Server Error', 500),
        ]);

        $job = new SendOrderToIntegrationJob($order->id);
        $job->handle(new OutboundIntegrationStub());

        Http::assertSentCount(3);

        $sync = OrderIntegration::where('order_id', $order->id)->first();
        $this->assertNotNull($sync);
        $this->assertSame(OrderIntegration::STATUS_FAILED, $sync->status);
        $this->assertStringContainsString('ERP connection failed', $sync->last_error);
    }

    public function test_webhook_callback_job_retries_on_failure(): void
    {
        Http::fake([
            'http://example.com/webhook' => Http::sequence()
                ->response('Gateway Timeout', 504)
                ->response('Success', 200),
        ]);

        $job = new \App\Jobs\Fmcg\SendWebhookCallbackJob('http://example.com/webhook', [
            'order_number' => 'ORD-123',
            'status' => 'acknowledged',
        ]);
        $job->handle();

        Http::assertSentCount(2);
    }

    public function test_erp_stub_controller_simulates_transient_failure_and_success(): void
    {
        config(['services.erp_stub.webhook_token' => 'test-token']);

        $customer = Customer::factory()->create();
        
        $order = Order::create([
            'id' => 15,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TRANSIENT-5',
            'status' => 'allocated',
            'currency' => 'USD',
            'subtotal' => 100,
            'total' => 100,
            'placed_at' => now(),
        ]);

        $this->postJson(
            route('integrations.erp-stub.orders'),
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'currency' => $order->currency,
                'total' => (string) $order->total,
                'status' => $order->status,
                'line_count' => 1,
                'callback_url' => 'http://localhost/callback',
            ],
            ['X-Integration-Token' => 'test-token']
        )->assertStatus(503);

        $this->postJson(
            route('integrations.erp-stub.orders'),
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'currency' => $order->currency,
                'total' => (string) $order->total,
                'status' => $order->status,
                'line_count' => 1,
                'callback_url' => 'http://localhost/callback',
            ],
            ['X-Integration-Token' => 'test-token']
        )->assertStatus(503);

        $response = $this->postJson(
            route('integrations.erp-stub.orders'),
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'currency' => $order->currency,
                'total' => (string) $order->total,
                'status' => $order->status,
                'line_count' => 1,
                'callback_url' => 'http://localhost/callback',
            ],
            ['X-Integration-Token' => 'test-token']
        );

        $response->assertOk();
        $response->assertJson([
            'accepted' => true,
            'external_reference' => 'ERP-ORD-TRANSIENT-5',
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
