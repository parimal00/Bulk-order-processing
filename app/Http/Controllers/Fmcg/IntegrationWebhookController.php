<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OrderIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationWebhookController extends Controller
{
    public function orderSync(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.erp_stub.webhook_token');
        $providedToken = (string) $request->header('X-Integration-Token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorized webhook token.'], 401);
        }

        $data = $request->validate([
            'provider' => ['nullable', 'string', 'max:64'],
            'external_reference' => ['nullable', 'string', 'max:128', 'required_without:order_number'],
            'order_number' => ['nullable', 'string', 'max:64', 'required_without:external_reference'],
            'status' => ['required', 'string', 'in:acknowledged,failed'],
            'message' => ['nullable', 'string', 'max:1000'],
            'payload' => ['nullable', 'array'],
        ]);

        $provider = $data['provider'] ?? 'erp_stub';

        $sync = OrderIntegration::query()
            ->where('provider', $provider)
            ->when(
                ! empty($data['external_reference']),
                fn ($query) => $query->where('external_reference', $data['external_reference'])
            )
            ->when(
                empty($data['external_reference']) && ! empty($data['order_number']),
                fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('order_number', $data['order_number']))
            )
            ->latest('id')
            ->first();

        if (! $sync) {
            return response()->json(['message' => 'Sync record not found.'], 404);
        }

        $sync->status = $data['status'] === 'acknowledged'
            ? OrderIntegration::STATUS_ACKNOWLEDGED
            : OrderIntegration::STATUS_FAILED;
        $sync->external_status = $data['status'];
        $sync->last_callback_at = now();
        $sync->response_payload = array_filter([
            'message' => $data['message'] ?? null,
            'payload' => $data['payload'] ?? null,
            'external_reference' => $data['external_reference'] ?? $sync->external_reference,
            'status' => $data['status'],
        ], fn ($value) => $value !== null);

        if ($sync->status === OrderIntegration::STATUS_FAILED) {
            $sync->last_error = $data['message'] ?? 'Integration callback reported failure.';
        } else {
            $sync->last_error = null;
        }

        $sync->save();

        AuditLog::log('integration_callback_received', OrderIntegration::class, $sync->id, [
            'provider' => $sync->provider,
            'order_number' => $sync->order?->order_number,
            'external_reference' => $sync->external_reference,
            'callback_status' => $data['status'],
            'message' => $data['message'] ?? null,
        ]);

        return response()->json([
            'message' => 'Callback accepted.',
            'sync_id' => $sync->id,
            'status' => $sync->status,
        ]);
    }
}
