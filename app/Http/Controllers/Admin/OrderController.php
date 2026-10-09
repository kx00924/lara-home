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
        $q = trim((string) $request->query('q'));
        $orders = Order::with(['user', 'design'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('id', ctype_digit(ltrim($q, '#')) ? (int) ltrim($q, '#') : 0)
                    ->orWhere('provider_ref', 'like', "%$q%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))
                    ->orWhereHas('design', fn ($d) => $d->where('title', 'like', "%$q%"));
            }))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.orders', ['orders' => $orders, 'status' => $status, 'statuses' => Order::STATUSES, 'q' => $q]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Order::STATUSES)]]);
        $this->setStatus($order, $data['status']);

        return back()->with('success', "Order marked {$data['status']}.");
    }

    /** Changes the status of many orders at once. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);
        $orders = Order::whereIn('id', $data['ids'])->get();
        $orders->each(fn (Order $order) => $this->setStatus($order, $data['action']));

        return back()->with('success', "{$orders->count()} order(s) marked {$data['action']}.");
    }

    /** Moves an order to a status, keeping the design's purchase count in step. */
    private function setStatus(Order $order, string $status): void
    {
        $prev = $order->status;
        if ($status === $prev) {
            return;
        }
        if ($status === 'paid') {
            $order->markPaid();

            return;
        }
        $order->update(['status' => $status]);
        if ($prev === 'paid') {
            $order->design()->decrement('purchases');
        }
    }
}
