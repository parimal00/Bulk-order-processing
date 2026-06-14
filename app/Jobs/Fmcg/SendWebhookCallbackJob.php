<?php

namespace App\Jobs\Fmcg;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $callbackUrl, public array $payload)
    {
    }

    public function handle(): void
    {
        $backoff = function (int $attempt, $exception = null) {
            $baseDelay = 100;
            $delay = $baseDelay * pow(2, $attempt - 1);
            $jitter = rand(0, 50);
            $totalDelay = (int) ($delay + $jitter);

            Log::warning("Webhook callback to {$this->callbackUrl} failed. Retrying attempt {$attempt} in {$totalDelay}ms.", [
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
            Http::withHeaders([
                'X-Integration-Token' => config('services.erp_stub.webhook_token'),
                'Accept' => 'application/json',
            ])
            ->retry(3, $backoff, $when, throw: true)
            ->post($this->callbackUrl, $this->payload);

            Log::info("Webhook callback successfully delivered to {$this->callbackUrl} for Order {$this->payload['order_number']}.");
        } catch (\Throwable $e) {
            Log::error("Webhook callback failed permanently to {$this->callbackUrl} for Order {$this->payload['order_number']}: " . $e->getMessage());
            throw $e;
        }
    }
}
