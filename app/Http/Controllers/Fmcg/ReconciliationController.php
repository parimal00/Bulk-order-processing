<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\ReconciliationResource;
use App\Jobs\Fmcg\SendOrderToIntegrationJob;
use App\Models\AuditLog;
use App\Models\OrderIntegration;
use App\Services\Fmcg\CircuitBreaker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    public function __construct(protected CircuitBreaker $circuitBreaker) {}

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
            'healthStatus' => [
                'provider' => 'erp_stub',
                'status' => $this->circuitBreaker->getStatus('erp_stub'),
                'failures' => (int) Cache::get('circuit_breaker:erp_stub:failures', 0),
                'cooldownRemaining' => $this->circuitBreaker->getCooldownRemaining('erp_stub'),
            ],
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

    public function updateCircuitBreaker(Request $request, string $provider): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:pause,resume,reset'],
        ]);

        $action = $request->string('action')->toString();

        if ($action === 'pause') {
            $this->circuitBreaker->pause($provider);
            AuditLog::log('integration_circuit_breaker_paused', null, null, [
                'provider' => $provider,
            ]);
            $message = 'Integration circuit has been paused.';
        } elseif ($action === 'resume') {
            $this->circuitBreaker->resume($provider);
            AuditLog::log('integration_circuit_breaker_resumed', null, null, [
                'provider' => $provider,
            ]);
            $message = 'Integration circuit has been resumed.';
        } else {
            $this->circuitBreaker->reset($provider);
            AuditLog::log('integration_circuit_breaker_reset', null, null, [
                'provider' => $provider,
            ]);
            $message = 'Integration circuit has been reset.';
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }
}
