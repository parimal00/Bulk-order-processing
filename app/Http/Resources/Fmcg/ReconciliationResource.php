<?php

namespace App\Http\Resources\Fmcg;

use App\Models\OrderIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ReconciliationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderIntegration $sync */
        $sync = $this->resource;

        return [
            'id' => $sync->id,
            'orderId' => $sync->order_id,
            'orderNo' => $sync->order?->order_number ?? "Order #{$sync->order_id}",
            'customer' => $sync->order?->customer?->name ?? 'Unknown',
            'provider' => $sync->provider,
            'internalState' => $sync->internal_status ?? $sync->order?->status ?? 'unknown',
            'externalState' => $sync->external_status ?? 'missing',
            'syncStatus' => $sync->status,
            'mismatch' => $this->mapMismatch($sync),
            'attempts' => $sync->attempt_count,
            'lastError' => $sync->last_error,
            'externalReference' => $sync->external_reference,
            'lastSync' => $sync->sent_at?->format('Y-m-d H:i:s') ?? $sync->updated_at?->format('Y-m-d H:i:s'),
            'lastCallbackAt' => $sync->last_callback_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function mapMismatch(OrderIntegration $sync): string
    {
        if ($sync->status === OrderIntegration::STATUS_ACKNOWLEDGED) {
            return 'none';
        }

        if ($sync->status === OrderIntegration::STATUS_SENT && in_array($sync->external_status, ['received', 'acknowledged'], true)) {
            return 'none';
        }

        if ($sync->external_status === 'timeout' || Str::contains(Str::lower((string) $sync->last_error), 'timeout')) {
            return 'sync_timeout';
        }

        if ($sync->status === OrderIntegration::STATUS_PENDING || $sync->external_reference === null) {
            return 'missing_external';
        }

        return 'qty_mismatch';
    }
}
