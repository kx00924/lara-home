@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-ink-muted">Overview of the last 30 days.</p>
        <a href="{{ route('admin.designs.create') }}" class="btn-primary"><x-icon name="plus" size="15" /> New design</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['Revenue', price($revenue), "$paidOrders paid orders", 'dollar'],
            ['Designs', $designs, "$published published · $free free", 'images'],
            ['Customers', $users, null, 'users'],
            ['Orders', $orders, ($orders - $paidOrders).' pending/failed', 'receipt'],
            ['Total views', compact_number($views), null, 'eye'],
        ] as [$label, $value, $sub, $icon])
            <div class="card flex items-start justify-between p-5">
                <div><p class="text-xs font-medium uppercase tracking-wider text-ink-muted">{{ $label }}</p><p class="mt-2 font-serif text-3xl">{{ $value }}</p>@if($sub)<p class="mt-1 text-xs text-ink-faint">{{ $sub }}</p>@endif</div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl2 bg-accent-soft text-accent"><x-icon :name="$icon" size="18" /></span>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        @php
            // Nice axis maximum: round up to 1/2/5 × 10^n so gridlines land on tidy values.
            $rawMax = max(1, $days->max('total'));
            $mag = 10 ** floor(log10($rawMax));
            $niceMax = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($v) => $v >= $rawMax);
            $W = 640; $H = 200; $padL = 44; $padR = 8; $padT = 12; $padB = 28;
            $plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;
            $n = $days->count(); $slot = $plotW / $n; $barW = max(4, $slot - 4);
            $ticks = [0, 0.25, 0.5, 0.75, 1];
        @endphp
        <div class="card p-6" x-data="{ hover: null }">
            <div class="mb-4 flex items-baseline justify-between">
                <div><h2 class="text-xl">Revenue, last 30 days</h2><p class="text-xs text-ink-faint">Paid orders per day</p></div>
                <div class="text-right"><p class="font-serif text-2xl">{{ price($days->sum('total')) }}</p><p class="text-xs text-ink-faint">{{ $days->sum('count') }} orders</p></div>
            </div>
            <div class="relative">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Daily revenue for the last 30 days">
                    {{-- grid + y axis --}}
                    @foreach($ticks as $t)
                        @php $y = $padT + $plotH - $t * $plotH; @endphp
                        <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y }}" y2="{{ $y }}" stroke="rgb(var(--c-text) / {{ $t === 0 ? '0.35' : '0.08' }})" stroke-width="1" />
                        <text x="{{ $padL - 8 }}" y="{{ $y + 3.5 }}" text-anchor="end" font-size="10" fill="rgb(var(--c-text) / 0.5)">{{ $site['currencySymbol'] }}{{ number_format($niceMax * $t) }}</text>
                    @endforeach
                    {{-- bars --}}
                    @foreach($days as $i => $d)
                        @php
                            $h = $d['total'] > 0 ? max(3, $d['total'] / $niceMax * $plotH) : 0;
                            $x = $padL + $i * $slot + ($slot - $barW) / 2;
                            $y = $padT + $plotH - $h;
                        @endphp
                        <g @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                            <rect x="{{ $padL + $i * $slot }}" y="{{ $padT }}" width="{{ $slot }}" height="{{ $plotH }}" fill="transparent" />
                            @if($h > 0)
                                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ $h }}" rx="3" fill="rgb(var(--c-text))" :fill-opacity="hover === {{ $i }} ? 1 : 0.7" class="transition" />
                            @else
                                <rect x="{{ $x }}" y="{{ $padT + $plotH - 2 }}" width="{{ $barW }}" height="2" fill="rgb(var(--c-text) / 0.18)" />
                            @endif
                        </g>
                        @if($i % 5 === 0 || $i === $n - 1)
                            <text x="{{ $padL + $i * $slot + $slot / 2 }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="rgb(var(--c-text) / 0.5)">{{ \Carbon\Carbon::parse($d['day'])->format('M j') }}</text>
                        @endif
                    @endforeach
                </svg>
                {{-- tooltip --}}
                @foreach($days as $i => $d)
                    <div x-cloak x-show="hover === {{ $i }}" class="pointer-events-none absolute -translate-x-1/2 rounded-lg border border-line bg-surface px-3 py-2 text-xs shadow-lift" style="left:{{ ($padL + $i * $slot + $slot / 2) / $W * 100 }}%; top:-4px">
                        <p class="font-medium">{{ \Carbon\Carbon::parse($d['day'])->format('D, M j') }}</p>
                        <p class="text-ink-muted">{{ price($d['total']) }} · {{ $d['count'] }} {{ $d['count'] === 1 ? 'order' : 'orders' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card p-6">
            <h2 class="mb-4 text-xl">Catalogue mix</h2>
            @foreach([['By room', $byRoom], ['By style', $byCategory]] as [$label, $items])
                <p class="label {{ $loop->last ? 'mt-5' : '' }}">{{ $label }}</p>
                @php $max = max(1, $items->max('designs_count')); @endphp
                <ul class="space-y-1.5">
                    @foreach($items as $i)
                        <li class="flex items-center gap-3 text-xs"><span class="w-28 truncate text-ink-muted">{{ $i->name }}</span><span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2"><span class="block h-full rounded-full bg-accent" style="width:{{ $i->designs_count / $max * 100 }}%"></span></span><span class="w-6 text-right">{{ $i->designs_count }}</span></li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card p-6">
            <h2 class="mb-4 text-xl">Top designs</h2>
            <ul class="divide-y divide-line">
                @foreach($topDesigns as $d)
                    <li class="flex items-center gap-3 py-3">
                        <img src="{{ thumb($d->cover, 160) }}" alt="" class="h-12 w-16 rounded-lg object-cover">
                        <div class="min-w-0 flex-1"><a href="{{ route('admin.designs.edit', $d) }}" class="block truncate text-sm font-medium hover:text-accent">{{ $d->title }}</a><p class="text-xs text-ink-faint">{{ compact_number($d->views) }} views · {{ $d->likes }} likes</p></div>
                        <div class="text-right text-sm"><p class="font-medium">{{ $d->purchases }} sold</p><p class="text-xs text-ink-faint">{{ price($d->price) }}</p></div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card p-6">
            <div class="mb-4 flex items-center justify-between"><h2 class="text-xl">Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="text-sm text-accent hover:underline">All orders</a></div>
            <ul class="divide-y divide-line">
                @foreach($recentOrders as $o)
                    <li class="flex items-center gap-3 py-3 text-sm">
                        <div class="min-w-0 flex-1"><p class="truncate font-medium">{{ $o->design?->title ?? 'Deleted design' }}</p><p class="text-xs text-ink-faint">{{ $o->user?->name }} · {{ $o->created_at->format('M j, Y') }}</p></div>
                        <span>{{ price($o->amount) }}</span>
                        <span class="badge {{ ['paid' => 'badge-free', 'pending' => 'badge-warn', 'failed' => 'badge-danger', 'refunded' => 'badge-neutral'][$o->status] }}">{{ $o->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
