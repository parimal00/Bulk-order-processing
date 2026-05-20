<?php

namespace App\Http\Resources\Fmcg;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalRequested = (int) $this->lines->sum('quantity');
        $totalAllocated = (int) $this->lines->sum('allocated_quantity');
        $fulfillment = $totalRequested > 0 ? round(($totalAllocated / $totalRequested) * 100) : 0;

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'placed_at' => $this->placed_at ? $this->placed_at->toIso8601String() : null,
            'policy_flags' => $this->all_policy_flags,
            'projected_margin' => $this->projected_margin ?? '22%',
            'customer_name' => $this->customer?->name ?? 'Unknown',
            'fulfillment' => $fulfillment,
            'lines' => OrderLineResource::collection($this->lines)->resolve(),
        ];
    }
}
