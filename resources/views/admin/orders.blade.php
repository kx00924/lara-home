@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div x-data="bulkTable()">
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" class="flex min-w-[240px] flex-1 items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2 sm:max-w-sm">
            <x-icon name="search" size="15" class="text-ink-faint" />
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ $q }}" placeholder="Search order #, customer, email, design…" class="w-full bg-transparent text-sm outline-none">
        </form>
        <div class="flex gap-1 rounded-pill bg-surface-2 p-1">
            @foreach(['', ...$statuses] as $s)
                <a href="{{ route('admin.orders.index', array_filter(['status' => $s, 'q' => $q])) }}" class="rounded-pill px-3 py-1.5 text-xs font-medium capitalize {{ $status === $s ? 'bg-surface shadow-soft' : 'text-ink-muted' }}">{{ $s ?: 'All' }}</a>
            @endforeach
        </div>
        <div class="ml-auto"><x-admin.per-page :paginator="$orders" /></div>
    </div>
    <div class="card overflow-x-auto">
        @if($orders->count())
            <table class="table-base">
                <thead><tr>
                    <th class="w-8"><input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" class="accent-accent" aria-label="Select all"></th>
                    <th class="w-12">No.</th>
                    <x-admin.sort-th key="id" label="Order" :sort="$sort" :dir="$dir" />
                    <th>Customer</th><th>Design</th>
                    <x-admin.sort-th key="amount" label="Amount" :sort="$sort" :dir="$dir" />
                    <x-admin.sort-th key="provider" label="Provider" :sort="$sort" :dir="$dir" />
                    <x-admin.sort-th key="status" label="Status" :sort="$sort" :dir="$dir" />
                    <x-admin.sort-th key="created_at" label="Date" :sort="$sort" :dir="$dir" />
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach($orders as $o)
                        <tr :class="selected.includes('{{ $o->id }}') ? 'bg-accent-soft/40' : ''">
                            <td><input type="checkbox" value="{{ $o->id }}" data-row-id="{{ $o->id }}" x-model="selected" class="accent-accent" aria-label="Select order {{ $o->id }}"></td>
                            <td class="text-xs text-ink-faint">{{ $orders->firstItem() + $loop->index }}</td>
                            <td class="font-mono text-xs text-ink-faint">#{{ $o->id }}</td>
                            <td><p class="font-medium">{{ $o->user?->name ?? '—' }}</p><p class="text-xs text-ink-faint">{{ $o->user?->email }}</p></td>
                            <td><div class="flex items-center gap-2">
                                @if($o->design?->cover)<button type="button" @click="$dispatch('open-image', { url: @js($o->design->cover), title: @js($o->design->title) })" class="h-9 w-12 shrink-0 overflow-hidden rounded-md transition hover:ring-2 hover:ring-accent/50" aria-label="View image"><img src="{{ thumb($o->design->cover, 120) }}" alt="" class="h-full w-full object-cover"></button>@endif
                                <span class="max-w-[220px] truncate">{{ $o->design?->title ?? 'Deleted design' }}</span>
                            </div></td>
                            <td>{{ price($o->amount) }}</td>
                            <td class="text-xs uppercase text-ink-muted">{{ $o->provider }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.orders.update', $o) }}">@csrf @method('PATCH')
                                    <select name="status" onfocus="this.dataset.prev = this.value" onchange="confirmSelect(this, 'order #{{ $o->id }} status')" class="input !w-auto !py-1 !text-xs capitalize">@foreach($statuses as $s)<option value="{{ $s }}" @selected($o->status === $s)>{{ $s }}</option>@endforeach</select>
                                </form>
                            </td>
                            <td class="text-xs text-ink-muted">{{ ($o->paid_at ?? $o->created_at)->format('M j, Y') }}</td>
                            <td class="text-right"><x-delete-form :action="route('admin.orders.destroy', $o)" :item="'order #'.$o->id" :message="$o->status === 'paid' ? 'This is a paid order: the customer loses access to the design and the sale is removed from the stats. This cannot be undone.' : 'This cannot be undone.'" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="py-14 text-center text-sm text-ink-muted">No orders match.</p>
        @endif
    </div>
    @include('admin.partials.bulk-bar', ['action' => route('admin.orders.bulk'), 'actions' => ['paid' => 'Mark paid', 'pending' => 'Mark pending', 'failed' => 'Mark failed', 'refunded' => 'Mark refunded', 'delete' => 'Delete']])
    {{ $orders->links() }}
</div>
@endsection
