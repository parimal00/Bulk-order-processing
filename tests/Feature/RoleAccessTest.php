<?php

namespace Tests\Feature;

use App\Models\BulkUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_can_access_operations_routes_but_not_review_or_admin_routes(): void
    {
        $ops = User::factory()->create(['role' => 'ops']);
        $this->actingAs($ops);

        $this->get(route('fmcg.uploads.index'))->assertOk();
        $this->get(route('fmcg.processing'))->assertOk();

        $this->get(route('fmcg.approvals'))->assertForbidden();
        $this->get(route('fmcg.orders.index'))->assertForbidden();
        $this->get(route('fmcg.settings.users-roles'))->assertForbidden();
    }

    public function test_approver_can_access_review_routes_but_not_operations_or_admin_routes(): void
    {
        $approver = User::factory()->create(['role' => 'approver']);
        $targetUser = User::factory()->create(['role' => 'ops']);
        $this->actingAs($approver);

        $this->get(route('fmcg.approvals'))->assertOk();
        $this->get(route('fmcg.orders.index'))->assertOk();
        $this->get(route('fmcg.reconciliation'))->assertOk();

        $this->get(route('fmcg.uploads.index'))->assertForbidden();
        $this->get(route('fmcg.settings.users-roles'))->assertForbidden();
        $this->put(route('fmcg.settings.users-roles.update', $targetUser), ['role' => 'admin'])->assertForbidden();
    }

    public function test_admin_can_access_operations_review_and_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('fmcg.uploads.index'))->assertOk();
        $this->get(route('fmcg.processing'))->assertOk();
        $this->get(route('fmcg.approvals'))->assertOk();
        $this->get(route('fmcg.orders.index'))->assertOk();
        $this->get(route('fmcg.reconciliation'))->assertOk();
        $this->get(route('fmcg.audit'))->assertOk();
        $this->get(route('fmcg.settings.users-roles'))->assertOk();
    }

    public function test_ops_processing_redirects_back_to_uploads_index_not_orders(): void
    {
        Queue::fake();

        $ops = User::factory()->create(['role' => 'ops']);
        $this->actingAs($ops);

        $upload = BulkUpload::create([
            'uploaded_by' => $ops->id,
            'original_filename' => 'sample.csv',
            'storage_path' => 'uploads/sample.csv',
            'file_hash' => str_repeat('a', 64),
            'file_type' => 'csv',
            'status' => BulkUpload::STATUS_VALID,
        ]);

        $response = $this->post(route('fmcg.bulk-uploads.process', $upload));

        $response->assertRedirect(route('fmcg.uploads.index'));
    }
}
