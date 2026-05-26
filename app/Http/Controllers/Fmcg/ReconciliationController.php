<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\ReconciliationResource;
use App\Jobs\Fmcg\SendOrderToIntegrationJob;
use App\Models\AuditLog;
use App\Models\OrderIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = OrderIntegration::query()
            ->with(['order.customer'])
            ->latest('id');

        if ($request->filled('provider')) {
            $query->where('provider', (string) $request->string('provider'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('order')) {
            $orderNo = (string) $request->string('order');
            $query->whereHas('order', fn ($q) => $q->where('order_number', 'like', "%{$orderNo}%"));
        }

        $rows = $query->take(100)->get();

        return Inertia::render('fmcg/reconciliation', [
            'rows' => ReconciliationResource::collection($rows)->resolve(),
            'filters' => $request->only(['provider', 'status', 'order']),
        ]);
    }

    public function retry(OrderIntegration $integration): RedirectResponse
    {
        SendOrderToIntegrationJob::dispatch($integration->order_id)->afterCommit();

        AuditLog::log('integration_sync_retry_requested', OrderIntegration::class, $integration->id, [
            'provider' => $integration->provider,
            'order_number' => $integration->order?->order_number,
        ]);

        return back()->with('success', 'Integration retry has been queued.');
    }
}
