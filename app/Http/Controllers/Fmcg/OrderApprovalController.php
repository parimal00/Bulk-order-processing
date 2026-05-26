<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\OrderApprovalResource;
use App\Jobs\Fmcg\SendOrderToIntegrationJob;
use App\Models\Order;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrderApprovalController extends Controller
{
    public function index()
    {
                $orders = Order::with('customer')
            ->whereNotIn('status', [Order::STATUS_APPROVED, Order::STATUS_REJECTED])
            ->latest('placed_at')
            ->get();

        return Inertia::render('fmcg/approvals', [
            'orders' => OrderApprovalResource::collection($orders)->resolve()
        ]);
    }

    public function approve(Order $order)
    {
        Gate::authorize('approve-order');

        if ($order->isFinalised()) {
            return back()->with('error', 'Order is already finalised and cannot be approved.');
        }

        $status = $order->determineFulfillmentStatus();
        
        $order->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLog::log('order_approved', Order::class, $order->id, [
            'order_number' => $order->order_number,
            'status_moved_to' => $status,
        ]);

        SendOrderToIntegrationJob::dispatch($order->id)->afterCommit();

        return back()->with('success', "Order {$order->order_number} has been approved and moved to {$status}.");
    }

    public function reject(Order $order, \App\Services\Fmcg\InventoryEngine $inventoryEngine)
    {
        Gate::authorize('approve-order');

        if ($order->isFinalised()) {
            return back()->with('error', 'Order is already finalised and cannot be rejected.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $inventoryEngine) {
            $inventoryEngine->releaseOrder($order);
            
            $order->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
            ]);
        });

        AuditLog::log('order_rejected', Order::class, $order->id, [
            'order_number' => $order->order_number,
            'inventory_released' => true,
        ]);

        return back()->with('success', "Order {$order->order_number} has been rejected and allocated stock has been released.");
    }
}
