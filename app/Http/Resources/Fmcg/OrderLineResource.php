<?php

namespace App\Http\Resources\Fmcg;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'product_name' => $this->product_name,
            'requested_qty' => $this->quantity,
            'allocated_qty' => $this->allocated_quantity,
            'backorder_qty' => $this->backorder_quantity,
            'unit_price' => '$' . number_format($this->unit_price, 2),
            'line_total' => '$' . number_format($this->line_total, 2),
            'allocation_status' => $this->allocation_status,
        ];
    }
}
