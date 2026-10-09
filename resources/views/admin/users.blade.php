@extends('layouts.admin')
@section('title', 'Customers')

@section('content')
<div x-data="bulkTable()">
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" class="flex min-w-[240px] flex-1 items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2 sm:max-w-sm"><x-icon name="search" size="15" class="text-ink-faint" /><input name="q" value="{{ $q }}" placeholder="Search name or email…" class="w-full bg-transparent text-sm outline-none"></form>
        <div class="ml-auto"><x-admin.per-page :paginator="$users" /></div>
    </div>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr>
                <th class="w-8"><input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" class="accent-accent" aria-label="Select all"></th>
                <th class="w-12">No.</th>
                <x-admin.sort-th key="name" label="Customer" :sort="$sort" :dir="$dir" />
                <x-admin.sort-th key="role" label="Role" :sort="$sort" :dir="$dir" />
                <x-admin.sort-th key="purchases_count" label="Purchases" :sort="$sort" :dir="$dir" />
                <x-admin.sort-th key="spent" label="Spent" :sort="$sort" :dir="$dir" />
                <x-admin.sort-th key="created_at" label="Joined" :sort="$sort" :dir="$dir" />
                <x-admin.sort-th key="is_active" label="Active" :sort="$sort" :dir="$dir" />
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($users as $u)
                    @php $me = $u->id === auth()->id(); @endphp
                    <tr :class="selected.includes('{{ $u->id }}') ? 'bg-accent-soft/40' : ''">
                        <td>@unless($me)<input type="checkbox" value="{{ $u->id }}" data-row-id="{{ $u->id }}" x-model="selected" class="accent-accent" aria-label="Select {{ $u->name }}">@endunless</td>
                        <td class="text-xs text-ink-faint">{{ $users->firstItem() + $loop->index }}</td>
                        <td><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-soft text-sm font-bold text-accent">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span><div><p class="font-medium">{{ $u->name }} @if($me)<span class="badge badge-neutral">you</span>@endif</p><p class="text-xs text-ink-faint">{{ $u->email }}</p></div></div></td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}">@csrf @method('PATCH')
                                <select name="role" onfocus="this.dataset.prev = this.value" onchange="confirmSelect(this, {{ Js::from('the role of '.$u->name) }})" class="input !w-auto !py-1 !text-xs" @disabled($me)><option value="customer" @selected($u->role === 'customer')>customer</option><option value="admin" @selected($u->role === 'admin')>admin</option></select>
                            </form>
                        </td>
                        <td>{{ $u->purchases_count }}</td>
                        <td>{{ price($u->spent ?? 0) }}</td>
                        <td class="text-xs text-ink-muted">{{ $u->created_at->format('M j, Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="0">
                                @if($me)<x-toggle name="is_active_disabled" :checked="true" />@else<x-toggle name="is_active" :checked="$u->is_active" submit :confirm="$u->name" />@endif
                            </form>
                        </td>
                        <td class="text-right">
                            @unless($me)
                                <x-delete-form :action="route('admin.users.destroy', $u)" :item="'the account of '.$u->name" message="Their orders stay on record, but they can no longer sign in. This cannot be undone." />
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-14 text-center text-sm text-ink-muted">No accounts match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.bulk-bar', ['action' => route('admin.users.bulk'), 'actions' => ['activate' => 'Activate', 'deactivate' => 'Deactivate', 'make_customer' => 'Set role: customer', 'make_admin' => 'Set role: admin', 'delete' => 'Delete']])
    {{ $users->links() }}
</div>
@endsection
