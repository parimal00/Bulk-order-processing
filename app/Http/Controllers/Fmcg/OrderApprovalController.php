<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\OrderApprovalResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderApprovalController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->where('status', 'pending_review')
            ->latest('placed_at')
            ->get();

        return Inertia::render('fmcg/approvals', [
            'orders' => OrderApprovalResource::collection($orders)->resolve()
        ]);
    }

    public function approve(Order $order)
    {
        if ($order->status !== 'pending_review') {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $status = $order->determineFulfillmentStatus();
        $order->update(['status' => $status]);

        return back()->with('success', "Order {$order->order_number} has been approved and moved to {$status}.");
    }

    public function reject(Order $order, \App\Services\Fmcg\InventoryEngine $inventoryEngine)
    {
        if ($order->status !== 'pending_review') {
            return back()->with('error', 'Only pending orders can be rejected.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $inventoryEngine) {
            $inventoryEngine->releaseOrder($order);
            $order->update(['status' => 'rejected']);
        });

        return back()->with('success', "Order {$order->order_number} has been rejected and allocated stock has been released.");
    }
}
