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
        } elseif ($this->action === 'integration_sync_sent') {
            $detailsSentence = "Outbound ERP stub sync dispatched successfully.";
        } elseif ($this->action === 'integration_sync_failed') {
            $detailsSentence = "Outbound ERP stub sync failed and requires retry.";
        } elseif ($this->action === 'integration_sync_retry_requested') {
            $detailsSentence = "Manual integration retry was requested from reconciliation view.";
        } elseif ($this->action === 'integration_callback_received') {
            $detailsSentence = "Webhook callback received from external integration.";
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
