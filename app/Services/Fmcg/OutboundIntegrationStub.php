<?php

namespace App\Services\Fmcg;

use App\Models\Order;
use App\Services\Fmcg\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutboundIntegrationStub
{
    protected CircuitBreaker $circuitBreaker;

    public function __construct(?CircuitBreaker $circuitBreaker = null)
    {
        $this->circuitBreaker = $circuitBreaker ?? app(CircuitBreaker::class);
    }

    public function provider(): string
    {
        return 'erp_stub';
    }

    /**
     * Build payload and return the response from the ERP service.
     * Implements HTTP client retries with exponential backoff and jitter.
     *
     * @return array{accepted: bool, external_reference: string|null, external_status: string, message: string, request: array<string, mixed>}
     */
    public function pushOrder(Order $order): array
    {
        $payload = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => [
                'id' => $order->customer_id,
                'name' => $order->customer?->name,
            ],
            'currency' => $order->currency,
            'total' => (string) $order->total,
            'status' => $order->status,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'line_count' => $order->lines->count(),
            'callback_url' => route('integrations.webhooks.order-sync', absolute: true),
        ];

        if ($order->status === Order::STATUS_PENDING_REVIEW || $order->status === Order::STATUS_REJECTED) {
            return [
                'accepted' => false,
                'external_reference' => null,
                'external_status' => 'rejected',
                'message' => 'Order is not in a syncable state.',
                'request' => $payload,
            ];
        }

        $provider = $this->provider();
        if (! $this->circuitBreaker->isAvailable($provider)) {
            $status = $this->circuitBreaker->getStatus($provider);
            return [
                'accepted' => false,
                'external_reference' => null,
                'external_status' => 'failed',
                'message' => "Circuit breaker is in {$status} state. Request blocked.",
                'request' => $payload,
            ];
        }

        $url = config('services.erp_stub.base_url') . '/integrations/erp-stub/orders';

        $backoff = function (int $attempt, $exception = null) use ($order) {
            $baseDelay = 100;
            $delay = $baseDelay * pow(2, $attempt - 1);
            $jitter = rand(0, 50);
            $totalDelay = (int) ($delay + $jitter);

            Log::warning("ERP integration call failed for Order {$order->order_number}. Retrying attempt {$attempt} in {$totalDelay}ms.", [
                'exception' => $exception?->getMessage(),
            ]);

            return $totalDelay;
        };

        $when = function ($exception, $request) {
            if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                return true;
            }
            if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                $status = $exception->response->status();
                return $status >= 500 || $status === 429;
            }
            return false;
        };

        try {
            $response = Http::withHeaders([
                'X-Integration-Token' => config('services.erp_stub.webhook_token'),
                'Accept' => 'application/json',
            ])
                ->retry(3, $backoff, $when, throw: true)
                ->post($url, $payload);

            $data = $response->json();

            $this->circuitBreaker->recordSuccess($provider);

            return [
                'accepted' => $data['accepted'] ?? false,
                'external_reference' => $data['external_reference'] ?? null,
                'external_status' => $data['external_status'] ?? 'received',
                'message' => $data['message'] ?? 'Order accepted by ERP stub.',
                'request' => $payload,
            ];
        } catch (\Throwable $e) {
            Log::error("ERP integration call failed permanently for Order {$order->order_number}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->circuitBreaker->recordFailure($provider);

            return [
                'accepted' => false,
                'external_reference' => null,
                'external_status' => 'failed',
                'message' => 'ERP connection failed: ' . $e->getMessage(),
                'request' => $payload,
            ];
        }
    }
}
