<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Jobs\Fmcg\SendWebhookCallbackJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ErpStubController extends Controller
{
    public function receiveOrder(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.erp_stub.webhook_token');
        $providedToken = (string) $request->header('X-Integration-Token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Unauthorized integration token.'], 401);
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'order_number' => ['required', 'string', 'max:64'],
            'customer' => ['required', 'array'],
            'customer.id' => ['required', 'integer'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:3'],
            'total' => ['required', 'string'],
            'status' => ['required', 'string', 'max:32'],
            'placed_at' => ['nullable', 'string'],
            'line_count' => ['required', 'integer'],
            'callback_url' => ['required', 'string', 'url'],
        ]);

        $orderId = (int) $data['order_id'];
        $orderNumber = $data['order_number'];
        $cacheKey = "erp_stub_attempts:{$orderNumber}";

        // Simulate permanent failures (e.g. if order ID is divisible by 10)
        if ($orderId % 10 === 0) {
            $attempts = (int) Cache::increment($cacheKey);
            Cache::put($cacheKey, $attempts, 300);

            Log::error("Simulated ERP permanent failure for Order {$orderNumber}. Attempt {$attempts}.");

            return response()->json([
                'accepted' => false,
                'external_reference' => null,
                'external_status' => 'failed',
                'message' => 'Simulated permanent ERP system error.',
            ], 500);
        }

        // Simulate transient failures (e.g. if order ID is divisible by 5)
        if ($orderId % 5 === 0) {
            $attempts = (int) Cache::increment($cacheKey);
            Cache::put($cacheKey, $attempts, 300);

            if ($attempts < 3) {
                Log::warning("Simulated ERP transient failure (503) for Order {$orderNumber}. Attempt {$attempts}.");

                return response()->json([
                    'accepted' => false,
                    'external_reference' => null,
                    'external_status' => 'timeout',
                    'message' => 'Simulated transient ERP failure. Try again.',
                ], 503);
            }

            Log::info("Simulated ERP transient failure recovered on attempt {$attempts} for Order {$orderNumber}.");
        }

        // Clean up cache on success
        Cache::forget($cacheKey);

        $externalReference = 'ERP-' . $orderNumber;

        // Dispatch background job to perform the webhook callback asynchronously
        SendWebhookCallbackJob::dispatch($data['callback_url'], [
            'provider' => 'erp_stub',
            'external_reference' => $externalReference,
            'order_number' => $orderNumber,
            'status' => 'acknowledged',
            'message' => 'Order successfully processed by simulated ERP.',
        ])->afterCommit();

        return response()->json([
            'accepted' => true,
            'external_reference' => $externalReference,
            'external_status' => 'received',
            'message' => 'Order accepted by ERP stub.',
        ], 200);
    }
}
