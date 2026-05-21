<?php

namespace App\Http\Resources\Fmcg;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $orderNo = $this->details['order_number'] ?? null;
        $uploadId = $this->details['upload_id'] ?? null;
        
        $entityLabel = $orderNo 
            ? $orderNo 
            : ($uploadId ? "UPL-{$uploadId}" : ($this->entity_type ? class_basename($this->entity_type) . " #{$this->entity_id}" : 'System'));

        // Beautifully format the details sentence
        $detailsSentence = '';
        if ($this->action === 'order_approved') {
            $detailsSentence = "Approved order {$orderNo} and moved status to '" . ($this->details['status_moved_to'] ?? '') . "'.";
        } elseif ($this->action === 'order_rejected') {
            $detailsSentence = "Rejected order {$orderNo} and successfully released reserved warehouse inventory.";
        } elseif ($this->action === 'order_created') {
            $detailsSentence = "Bulk order shell spawned successfully from CSV upload source.";
        } elseif ($this->action === 'upload_processed') {
            $detailsSentence = "Completed pricing engines and stock allocation pass for upload.";
        } else {
            $detailsSentence = $this->details ? json_encode($this->details) : 'Executed system task.';
        }

        return [
            'id' => $this->id,
            'timestamp' => $this->created_at->format('Y-m-d H:i:s'),
            'entity' => $entityLabel,
            'action' => Str::title(str_replace('_', ' ', $this->action)),
            'actor' => $this->user ? $this->user->name : 'System Job',
            'details' => $detailsSentence,
        ];
    }
}
