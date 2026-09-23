<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(29)->startOfDay();
        $series = Order::where('status', 'paid')->where('paid_at', '>=', $since)->get()
            ->groupBy(fn ($o) => $o->paid_at->format('Y-m-d'))
            ->map(fn ($g) => ['total' => $g->sum('amount'), 'count' => $g->count()]);
        $days = collect(range(29, 0))->map(function ($i) use ($series) {
            $d = now()->subDays($i)->format('Y-m-d');

            return ['day' => $d, 'total' => $series[$d]['total'] ?? 0, 'count' => $series[$d]['count'] ?? 0];
        });

        return view('admin.dashboard', [
            'designs' => Design::count(),
            'published' => Design::where('published', true)->count(),
            'free' => Design::where('price', '<=', 0)->count(),
            'users' => User::where('role', 'customer')->count(),
            'orders' => Order::count(),
            'paidOrders' => Order::where('status', 'paid')->count(),
            'revenue' => (float) Order::where('status', 'paid')->sum('amount'),
            'views' => (int) Design::sum('views'),
            'days' => $days,
            'maxRevenue' => max(1, $days->max('total')),
            'byRoom' => RoomType::withCount('designs')->orderByDesc('designs_count')->get(),
            'byCategory' => Category::withCount('designs')->orderByDesc('designs_count')->get(),
            'topDesigns' => Design::orderByDesc('purchases')->orderByDesc('views')->limit(6)->get(),
            'recentOrders' => Order::with(['user', 'design'])->latest()->limit(8)->get(),
        ]);
    }
}
