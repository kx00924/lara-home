<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public const RANGES = ['7d' => 'Last 7 days', '30d' => 'Last 30 days', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year', 'custom' => 'Custom period'];

    public function __invoke(Request $request)
    {
        [$range, $from, $to] = $this->period($request);
        $byMonth = $from->diffInDays($to) > 92;
        $paidInPeriod = fn () => Order::where('status', 'paid')->whereBetween('paid_at', [$from, $to]);

        $series = $paidInPeriod()->get()
            ->groupBy(fn ($o) => $o->paid_at->format($byMonth ? 'Y-m' : 'Y-m-d'))
            ->map(fn ($g) => ['total' => $g->sum('amount'), 'count' => $g->count()]);
        $buckets = collect();
        for ($cursor = $byMonth ? $from->startOfMonth() : $from; $cursor <= $to; $cursor = $byMonth ? $cursor->addMonth() : $cursor->addDay()) {
            $key = $cursor->format($byMonth ? 'Y-m' : 'Y-m-d');
            $buckets->push([
                'day' => $key,
                'label' => $cursor->format($byMonth ? 'M Y' : 'M j'),
                'tooltip' => $cursor->format($byMonth ? 'F Y' : 'D, M j'),
                'total' => $series[$key]['total'] ?? 0,
                'count' => $series[$key]['count'] ?? 0,
            ]);
        }

        return view('admin.dashboard', [
            'range' => $range,
            'ranges' => self::RANGES,
            'from' => $from,
            'to' => $to,
            'label' => $range === 'custom' ? $from->format('M j, Y').' – '.$to->format('M j, Y') : self::RANGES[$range],
            'byMonth' => $byMonth,
            'designs' => Design::count(),
            'published' => Design::where('published', true)->count(),
            'free' => Design::where('price', '<=', 0)->count(),
            'users' => User::where('role', 'customer')->count(),
            'newUsers' => User::where('role', 'customer')->whereBetween('created_at', [$from, $to])->count(),
            'orders' => Order::whereBetween('created_at', [$from, $to])->count(),
            'paidOrders' => $paidInPeriod()->count(),
            'revenue' => (float) $paidInPeriod()->sum('amount'),
            'views' => (int) Design::sum('views'),
            'days' => $buckets,
            'byRoom' => RoomType::withCount('designs')->orderByDesc('designs_count')->get(),
            'byCategory' => Category::withCount('designs')->orderByDesc('designs_count')->get(),
            'topDesigns' => Design::withCount(['orders as period_sales' => fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to])])
                ->withSum(['orders as period_revenue' => fn ($q) => $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to])], 'amount')
                ->orderByDesc('period_sales')->orderByDesc('views')->limit(6)->get(),
            'recentOrders' => Order::with(['user', 'design'])->whereBetween('created_at', [$from, $to])->latest()->limit(8)->get(),
        ]);
    }

    /**
     * The reporting period from the query string: a preset range, or a custom from/to.
     *
     * @return array{0: string, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    private function period(Request $request): array
    {
        $range = array_key_exists($request->query('range'), self::RANGES) ? $request->query('range') : '30d';
        $now = CarbonImmutable::now();
        $to = $now->endOfDay();
        $from = match ($range) {
            '7d' => $now->subDays(6)->startOfDay(),
            'week' => $now->startOfWeek(),
            'month' => $now->startOfMonth(),
            'year' => $now->startOfYear(),
            'custom' => rescue(fn () => CarbonImmutable::parse($request->query('from'))->startOfDay(), $now->subDays(29)->startOfDay(), false),
            default => $now->subDays(29)->startOfDay(),
        };
        if ($range === 'custom') {
            $to = rescue(fn () => CarbonImmutable::parse($request->query('to'))->endOfDay(), $to, false);
            if ($to < $from) {
                [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
            }
        }

        return [$range, $from, $to];
    }
}
