<?php

namespace App\Http\Resources\Fmcg;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderNo' => $this->order_number,
            'customer' => $this->customer?->name ?? 'Unknown',
            'amount' => '$' . number_format($this->total, 2),
            'submittedAt' => $this->placed_at ? $this->placed_at->diffForHumans() : 'Unknown',
            'risk' => $this->risk_level,
            'margin' => $this->projected_margin ?? '22%',
            'reasons' => count($this->all_policy_flags) > 0 ? $this->all_policy_flags : ['Standard Order'],
        ];
    }
}
