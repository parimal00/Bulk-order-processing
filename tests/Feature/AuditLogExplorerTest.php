<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BulkUpload;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogExplorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_audit_logs_by_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $actor = User::factory()->create(['name' => 'Order Approver']);

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'order_approved',
            'entity_type' => Order::class,
            'entity_id' => 501,
            'details' => ['order_number' => 'ORD-ALPHA-1001'],
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'integration_sync_failed',
            'entity_type' => OrderIntegration::class,
            'entity_id' => 601,
            'details' => ['order_number' => 'ORD-BETA-2002'],
        ]);

        $this->actingAs($admin)
            ->get(route('fmcg.audit', ['order' => 'ORD-BETA-2002']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fmcg/audit')
                ->where('filters.order', 'ORD-BETA-2002')
                ->has('auditTrail', 1)
                ->where('auditTrail.0.entity', 'ORD-BETA-2002')
                ->where('auditTrail.0.action', 'Integration Sync Failed'),
            );
    }

    public function test_admin_can_filter_audit_logs_by_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => null,
            'action' => 'upload_processed',
            'entity_type' => BulkUpload::class,
            'entity_id' => 77,
            'details' => ['upload_id' => 77],
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'order_rejected',
            'entity_type' => Order::class,
            'entity_id' => 12,
            'details' => ['order_number' => 'ORD-NOPE-1000'],
        ]);

        $this->actingAs($admin)
            ->get(route('fmcg.audit', ['upload' => 'UPL-77']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fmcg/audit')
                ->where('filters.upload', 'UPL-77')
                ->has('auditTrail', 1)
                ->where('auditTrail.0.entity', 'UPL-77')
                ->where('auditTrail.0.action', 'Upload Processed'),
            );
    }

    public function test_admin_can_filter_audit_logs_by_user_and_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['name' => 'Target User', 'email' => 'target@example.com']);
        $otherUser = User::factory()->create(['name' => 'Other User', 'email' => 'other@example.com']);

        $matchLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user_role_updated',
            'entity_type' => User::class,
            'entity_id' => $targetUser->id,
            'details' => [
                'updated_user_id' => $targetUser->id,
                'updated_user_name' => $targetUser->name,
                'old_role' => 'ops',
                'new_role' => 'approver',
            ],
        ]);

        $oldLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user_role_updated',
            'entity_type' => User::class,
            'entity_id' => $otherUser->id,
            'details' => [
                'updated_user_id' => $otherUser->id,
                'updated_user_name' => $otherUser->name,
                'old_role' => 'ops',
                'new_role' => 'admin',
            ],
        ]);

        DB::table('audit_logs')->where('id', $matchLog->id)->update([
            'created_at' => now()->subDay()->setTime(11, 0),
        ]);

        DB::table('audit_logs')->where('id', $oldLog->id)->update([
            'created_at' => now()->subDays(5)->setTime(9, 0),
        ]);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $this->actingAs($admin)
            ->get(route('fmcg.audit', [
                'user' => 'target@example.com',
                'from' => $from,
                'to' => $to,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('fmcg/audit')
                ->where('filters.user', 'target@example.com')
                ->where('filters.from', $from)
                ->where('filters.to', $to)
                ->has('auditTrail', 1)
                ->where('auditTrail.0.entity', "User #{$targetUser->id}"),
            );
    }
}
