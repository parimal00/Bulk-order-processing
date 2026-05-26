<?php

namespace Tests\Feature;

use App\Jobs\Fmcg\SendOrderToIntegrationJob;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReconciliationViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_can_view_reconciliation_rows(): void
    {
        $approver = User::factory()->create(['role' => 'approver']);
        $customer = Customer::factory()->create();

        $order = Order::create([
            'customer_id' => $customer->id,
            'created_by' => $approver->id,
            'order_number' => 'ORD-REC-1001',
            'status' => 'allocated',
            'currency' => 'USD',
            'subtotal' => 125.50,
            'total' => 125.50,
            'placed_at' => now(),
        ]);

        OrderIntegration::create([
            'order_id' => $order->id,
            'provider' => 'erp_stub',
            'status' => OrderIntegration::STATUS_FAILED,
            'internal_status' => 'allocated',
            'external_status' => 'timeout',
            'attempt_count' => 2,
            'last_error' => 'Simulated ERP timeout.',
        ]);

        $this->actingAs($approver)
            ->get(route('fmcg.reconciliation'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fmcg/reconciliation')
                ->has('rows', 1)
                ->where('rows.0.orderNo', 'ORD-REC-1001')
                ->where('rows.0.syncStatus', OrderIntegration::STATUS_FAILED)
                ->where('rows.0.mismatch', 'sync_timeout'),
            );
    }

    public function test_retry_action_requeues_integration_job(): void
    {
        Queue::fake();

        $approver = User::factory()->create(['role' => 'approver']);
        $customer = Customer::factory()->create();

        $order = Order::create([
            'customer_id' => $customer->id,
            'created_by' => $approver->id,
            'order_number' => 'ORD-REC-2001',
            'status' => 'partially_fulfilled',
            'currency' => 'USD',
            'subtotal' => 340.00,
            'total' => 340.00,
            'placed_at' => now(),
        ]);

        $integration = OrderIntegration::create([
            'order_id' => $order->id,
            'provider' => 'erp_stub',
            'status' => OrderIntegration::STATUS_FAILED,
            'internal_status' => 'partially_fulfilled',
            'external_status' => 'rejected',
            'attempt_count' => 1,
        ]);

        $this->actingAs($approver)
            ->from(route('fmcg.reconciliation'))
            ->post(route('fmcg.reconciliation.retry', $integration))
            ->assertRedirect(route('fmcg.reconciliation'));

        Queue::assertPushed(SendOrderToIntegrationJob::class, fn (SendOrderToIntegrationJob $job) => $job->orderId === $order->id);

        $this->assertDatabaseHas((new AuditLog())->getTable(), [
            'action' => 'integration_sync_retry_requested',
            'entity_type' => OrderIntegration::class,
            'entity_id' => $integration->id,
        ]);
    }
}
