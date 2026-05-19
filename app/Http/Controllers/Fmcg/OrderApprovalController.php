<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
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
            ->get()
            ->map(function ($order) {
                // Determine risk and reasons based on real policy flags and order total
                $flags = $order->policy_flags ?? [];

                // Dynamically flag large orders if not already flagged by engine
                if ($order->total > 10000 && !in_array('Large Order Value', $flags)) {
                    $flags[] = 'Large Order Value';
                }

                $risk = 'low';
                if (count($flags) > 0) {
                    $risk = 'medium';
                    foreach ($flags as $flag) {
                        if (str_contains(strtolower($flag), 'moq') || str_contains(strtolower($flag), 'large')) {
                            $risk = 'high';
                            break;
                        }
                    }
                }

                return [
                    'id' => $order->id,
                    'orderNo' => $order->order_number,
                    'customer' => $order->customer?->name ?? 'Unknown',
                    'amount' => '$' . number_format($order->total, 2),
                    'submittedAt' => $order->placed_at ? $order->placed_at->diffForHumans() : 'Unknown',
                    'risk' => $risk,
                    'margin' => $order->projected_margin ?? '22%',
                    'reasons' => count($flags) > 0 ? $flags : ['Standard Order'],
                ];
            });

        return Inertia::render('fmcg/approvals', [
            'orders' => $orders
        ]);
    }

    public function approve(Order $order)
    {
        if ($order->status !== 'pending_review') {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $order->update(['status' => 'approved']);

        return back()->with('success', "Order {$order->order_number} has been approved.");
    }

    public function reject(Order $order)
    {
        if ($order->status !== 'pending_review') {
            return back()->with('error', 'Only pending orders can be rejected.');
        }

        $order->update(['status' => 'rejected']);

        return back()->with('success', "Order {$order->order_number} has been rejected.");
    }
}
