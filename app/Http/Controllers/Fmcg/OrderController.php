<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\OrderDetailResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->latest('placed_at')
            ->paginate(50);

        return Inertia::render('fmcg/orders/index', [
            'orders' => $orders
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'lines.product']);

        return Inertia::render('fmcg/orders/show', [
            'order' => new OrderDetailResource($order)
        ]);
    }
}
