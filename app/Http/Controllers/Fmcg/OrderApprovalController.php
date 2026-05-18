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
                // Simulate risk and reasons for now based on total amount
                $risk = 'low';
                $reasons = [];
                $margin = '22%';

                if ($order->total > 10000) {
                    $risk = 'high';
                    $reasons[] = 'Large Order Value';
                    $margin = '18%'; // high volume discount simulated
                } elseif ($order->total > 5000) {
                    $risk = 'medium';
                    $reasons[] = 'Moderate Order Value';
                    $margin = '20%';
                }

                if ($order->lines()->count() > 50) {
                    $risk = $risk === 'low' ? 'medium' : 'high';
                    $reasons[] = 'High Line Item Count';
                }

                return [
                    'id' => $order->id,
                    'orderNo' => $order->order_number,
                    'customer' => $order->customer?->name ?? 'Unknown',
                    'amount' => '$' . number_format($order->total, 2),
                    'submittedAt' => $order->placed_at ? $order->placed_at->diffForHumans() : 'Unknown',
                    'risk' => $risk,
                    'margin' => $margin,
                    'reasons' => count($reasons) > 0 ? $reasons : ['Standard Order'],
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
