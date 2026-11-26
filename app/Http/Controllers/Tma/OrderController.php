<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('tma.orders', [
            'orders' => $request->user()->orders()->withCount('items')->latest('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('tma.order', ['order' => $order->load('items')]);
    }
}
