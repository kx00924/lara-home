<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), Order::STATUSES) ? $request->query('status') : '';
        $orders = Order::with(['user', 'design'])->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(25)->withQueryString();

        return view('admin.orders', ['orders' => $orders, 'status' => $status, 'statuses' => Order::STATUSES]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Order::STATUSES)]]);
        $prev = $order->status;
        if ($data['status'] === 'paid' && $prev !== 'paid') {
            $order->markPaid();
        } else {
            $order->update(['status' => $data['status']]);
            if ($prev === 'paid') {
                $order->design()->decrement('purchases');
            }
        }

        return back()->with('success', "Order marked {$data['status']}.");
    }
}
