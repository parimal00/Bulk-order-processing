<?php

namespace App\Services\Fmcg;

use App\Models\Order;

class OutboundIntegrationStub
{
    public function provider(): string
    {
        return 'erp_stub';
    }

    /**
     * Build payload and return a deterministic stub response.
     * This simulates external behavior without making network calls.
     *
     * @return array{accepted: bool, external_reference: string|null, external_status: string, message: string, request: array<string, mixed>}
     */
    public function pushOrder(Order $order): array
    {
        $payload = [
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

        if ((int) $order->id % 5 === 0) {
            return [
                'accepted' => false,
                'external_reference' => null,
                'external_status' => 'timeout',
                'message' => 'Simulated ERP timeout. Retry is safe.',
                'request' => $payload,
            ];
        }

        return [
            'accepted' => true,
            'external_reference' => 'ERP-'.$order->order_number,
            'external_status' => 'received',
            'message' => 'Order accepted by ERP stub.',
            'request' => $payload,
        ];
    }
}
