<?php

namespace Tests\Feature;

use App\Models\BulkUpload;
use App\Models\Customer;
use App\Models\FailedBulkRow;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FmcgDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_operational_metrics_from_live_data(): void
    {
        $ops = User::factory()->create(['role' => 'ops']);
        $customer = Customer::factory()->create();

        $todayUpload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => $ops->id,
            'original_filename' => 'today.csv',
            'storage_path' => 'uploads/today.csv',
            'file_hash' => str_repeat('a', 64),
            'file_type' => 'csv',
            'status' => BulkUpload::STATUS_PROCESSED,
            'total_rows' => 100,
            'valid_rows' => 90,
            'invalid_rows' => 10,
            'started_at' => now()->subMinutes(8),
            'finished_at' => now()->subMinutes(3),
        ]);

        $activeUpload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => $ops->id,
            'original_filename' => 'active.csv',
            'storage_path' => 'uploads/active.csv',
            'file_hash' => str_repeat('b', 64),
            'file_type' => 'csv',
            'status' => BulkUpload::STATUS_PROCESSING,
            'total_rows' => 80,
            'valid_rows' => 70,
            'invalid_rows' => 10,
        ]);

        $yesterdayUpload = BulkUpload::create([
            'customer_id' => $customer->id,
            'uploaded_by' => $ops->id,
            'original_filename' => 'yesterday.csv',
            'storage_path' => 'uploads/yesterday.csv',
            'file_hash' => str_repeat('c', 64),
            'file_type' => 'csv',
            'status' => BulkUpload::STATUS_PROCESSED,
            'total_rows' => 50,
            'valid_rows' => 45,
            'invalid_rows' => 5,
            'started_at' => now()->subDay()->subMinutes(10),
            'finished_at' => now()->subDay()->subMinutes(3),
        ]);

        DB::table('bulk_uploads')->where('id', $yesterdayUpload->id)->update([
            'created_at' => now()->subDay()->setTime(9, 0),
            'updated_at' => now()->subDay()->setTime(9, 7),
        ]);

        $pendingOne = Order::create([
            'bulk_upload_id' => $todayUpload->id,
            'customer_id' => $customer->id,
            'created_by' => $ops->id,
            'order_number' => 'ORD-DASH-001',
            'status' => Order::STATUS_PENDING_REVIEW,
            'currency' => 'USD',
            'subtotal' => 350.00,
            'total' => 350.00,
            'placed_at' => now()->subMinutes(5),
        ]);

        $pendingTwo = Order::create([
            'bulk_upload_id' => $todayUpload->id,
            'customer_id' => $customer->id,
            'created_by' => $ops->id,
            'order_number' => 'ORD-DASH-002',
            'status' => Order::STATUS_PENDING_REVIEW,
            'currency' => 'USD',
            'subtotal' => 150.00,
            'total' => 150.00,
            'placed_at' => now()->subMinutes(4),
        ]);

        $syncedOrder = Order::create([
            'bulk_upload_id' => $todayUpload->id,
            'customer_id' => $customer->id,
            'created_by' => $ops->id,
            'order_number' => 'ORD-DASH-003',
            'status' => 'allocated',
            'currency' => 'USD',
            'subtotal' => 220.00,
            'total' => 220.00,
            'placed_at' => now()->subMinutes(2),
        ]);

        DB::table('orders')->where('id', $pendingTwo->id)->update([
            'created_at' => now()->subDay()->setTime(8, 20),
            'updated_at' => now()->subDay()->setTime(8, 20),
        ]);

        OrderIntegration::create([
            'order_id' => $syncedOrder->id,
            'provider' => 'erp_stub',
            'status' => OrderIntegration::STATUS_FAILED,
            'internal_status' => 'allocated',
            'external_status' => 'timeout',
            'attempt_count' => 3,
            'last_error' => 'Timeout',
            'sent_at' => now()->subMinutes(20),
        ]);

        FailedBulkRow::create([
            'bulk_upload_id' => $todayUpload->id,
            'row_number' => 12,
            'sku' => 'SKU-1',
            'quantity' => 2,
            'error_code' => 'QTY_INVALID',
            'error_message' => 'Quantity invalid.',
            'raw_data' => ['sku' => 'SKU-1', 'quantity' => 'x'],
        ]);

        DB::table('order_lines')->insert([
            [
                'order_id' => $pendingOne->id,
                'product_id' => null,
                'sku' => 'SKU-A',
                'product_name' => 'Test A',
                'quantity' => 100,
                'allocated_quantity' => 70,
                'backorder_quantity' => 30,
                'unit_price' => 1.50,
                'line_total' => 150.00,
                'allocation_status' => 'partially_fulfilled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($ops)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fmcg/dashboard')
                ->has('kpis', 5)
                ->where('kpis.0.label', 'Today Uploads')
                ->where('kpis.0.value', '2')
                ->where('kpis.1.label', 'Lines Processed')
                ->where('kpis.1.value', '90')
                ->where('kpis.4.label', 'Pending Approvals')
                ->where('kpis.4.value', '2')
                ->has('throughput', 8)
                ->has('failures', 7)
                ->where('atRisk.sync.title', 'ERP sync stalled: ORD-DASH-003')
                ->where('atRisk.inventory.title', 'Inventory split risk')
                ->has('recentUploads')
                ->has('processingJobs', 1)
                ->where('processingJobs.0.uploadId', "UPL-{$activeUpload->id}")
                ->has('headerActions', 2),
            );
    }
}
