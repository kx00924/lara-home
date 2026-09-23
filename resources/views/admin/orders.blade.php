@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="mb-5 flex flex-wrap items-center gap-3">
    <div class="flex gap-1 rounded-pill bg-surface-2 p-1">
        @foreach(['', ...$statuses] as $s)
            <a href="{{ route('admin.orders.index', array_filter(['status' => $s])) }}" class="rounded-pill px-3 py-1.5 text-xs font-medium capitalize {{ $status === $s ? 'bg-surface shadow-soft' : 'text-ink-muted' }}">{{ $s ?: 'All' }}</a>
        @endforeach
    </div>
    <span class="ml-auto text-sm text-ink-muted">{{ $orders->total() }} orders</span>
</div>
<div class="card overflow-x-auto">
    @if($orders->count())
        <table class="table-base">
            <thead><tr><th>Order</th><th>Customer</th><th>Design</th><th>Amount</th><th>Provider</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($orders as $o)
                    <tr>
                        <td class="font-mono text-xs text-ink-faint">#{{ $o->id }}</td>
                        <td><p class="font-medium">{{ $o->user?->name ?? '—' }}</p><p class="text-xs text-ink-faint">{{ $o->user?->email }}</p></td>
                        <td><div class="flex items-center gap-2">@if($o->design?->cover_image)<img src="{{ thumb($o->design->cover_image, 120) }}" alt="" class="h-9 w-12 rounded-md object-cover">@endif<span class="max-w-[220px] truncate">{{ $o->design?->title ?? 'Deleted design' }}</span></div></td>
                        <td>{{ price($o->amount) }}</td>
                        <td class="text-xs uppercase text-ink-muted">{{ $o->provider }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.orders.update', $o) }}">@csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="input !w-auto !py-1 !text-xs capitalize">@foreach($statuses as $s)<option value="{{ $s }}" @selected($o->status === $s)>{{ $s }}</option>@endforeach</select>
                            </form>
                        </td>
                        <td class="text-xs text-ink-muted">{{ ($o->paid_at ?? $o->created_at)->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="py-14 text-center text-sm text-ink-muted">No orders.</p>
    @endif
</div>
{{ $orders->links() }}
@endsection
