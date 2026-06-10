<?php

namespace App\Jobs\Fmcg;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Services\Fmcg\OutboundIntegrationStub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SendOrderToIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;

    // public $queue = 'integrations';

    /**
     * @var int[]
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $orderId)
    {
    }

    public function handle(OutboundIntegrationStub $integrationStub): void
    {
        $order = Order::with(['customer', 'lines'])->find($this->orderId);

        if (! $order) {
            return;
        }

        $sync = OrderIntegration::firstOrCreate(
            ['order_id' => $order->id, 'provider' => $integrationStub->provider()],
            [
                'status' => OrderIntegration::STATUS_PENDING,
                'internal_status' => $order->status,
            ]
        );

        if ($sync->status === OrderIntegration::STATUS_ACKNOWLEDGED) {
            return;
        }

        $throttleKey = "integrations:outbound:{$sync->provider}:customer:{$order->customer_id}";

        try {
            Redis::throttle($throttleKey)->allow(5)->every(1)->then(
                function () use ($order, $sync, $integrationStub): void {
                    $this->performSync($order, $sync, $integrationStub);
                },
                function (): void {
                    $this->release(1);
                }
            );
        } catch (Throwable) {
            $this->fallbackWithCacheLimiter($throttleKey, $order, $sync, $integrationStub);
        }
    }

    private function fallbackWithCacheLimiter(
        string $throttleKey,
        Order $order,
        OrderIntegration $sync,
        OutboundIntegrationStub $integrationStub
    ): void {
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $retryAfterSeconds = RateLimiter::availableIn($throttleKey);
            $this->release(max(1, $retryAfterSeconds));

            return;
        }

        RateLimiter::hit($throttleKey, 1);
        $this->performSync($order, $sync, $integrationStub);
    }

    private function performSync(Order $order, OrderIntegration $sync, OutboundIntegrationStub $integrationStub): void
    {
        $result = $integrationStub->pushOrder($order);

        $sync->attempt_count = $sync->attempt_count + 1;
        $sync->internal_status = $order->status;
        $sync->external_status = $result['external_status'];
        $sync->request_payload = $result['request'];
        $sync->response_payload = [
            'message' => $result['message'],
            'accepted' => $result['accepted'],
            'external_status' => $result['external_status'],
            'external_reference' => $result['external_reference'],
        ];

        if ($result['accepted']) {
            $sync->status = OrderIntegration::STATUS_SENT;
            $sync->external_reference = $result['external_reference'];
            $sync->sent_at = now();
            $sync->last_error = null;

            AuditLog::log('integration_sync_sent', OrderIntegration::class, $sync->id, [
                'order_number' => $order->order_number,
                'provider' => $sync->provider,
                'external_reference' => $sync->external_reference,
                'status' => $sync->status,
            ]);
        } else {
            $sync->status = OrderIntegration::STATUS_FAILED;
            $sync->last_error = $result['message'];

            AuditLog::log('integration_sync_failed', OrderIntegration::class, $sync->id, [
                'order_number' => $order->order_number,
                'provider' => $sync->provider,
                'status' => $sync->status,
                'reason' => $result['message'],
            ]);
        }

        $sync->save();
    }
}
