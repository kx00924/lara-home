@extends('layouts.admin')
@section('title', 'Customers')

@section('content')
<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex min-w-[240px] flex-1 items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2 sm:max-w-sm"><x-icon name="search" size="15" class="text-ink-faint" /><input name="q" value="{{ $q }}" placeholder="Search name or email…" class="w-full bg-transparent text-sm outline-none"></form>
    <span class="ml-auto text-sm text-ink-muted">{{ $users->total() }} accounts</span>
</div>
<div class="card overflow-x-auto">
    <table class="table-base">
        <thead><tr><th>Customer</th><th>Role</th><th>Purchases</th><th>Spent</th><th>Joined</th><th>Active</th><th></th></tr></thead>
        <tbody>
            @foreach($users as $u)
                @php $me = $u->id === auth()->id(); @endphp
                <tr>
                    <td><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-soft text-sm font-bold text-accent">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span><div><p class="font-medium">{{ $u->name }} @if($me)<span class="badge badge-neutral">you</span>@endif</p><p class="text-xs text-ink-faint">{{ $u->email }}</p></div></div></td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.update', $u) }}">@csrf @method('PATCH')
                            <select name="role" onchange="this.form.submit()" class="input !w-auto !py-1 !text-xs" @disabled($me)><option value="customer" @selected($u->role === 'customer')>customer</option><option value="admin" @selected($u->role === 'admin')>admin</option></select>
                        </form>
                    </td>
                    <td>{{ $u->purchases_count }}</td>
                    <td>{{ price($u->spent ?? 0) }}</td>
                    <td class="text-xs text-ink-muted">{{ $u->created_at->format('M j, Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.update', $u) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="0">
                            @if($me)<x-toggle name="is_active_disabled" :checked="true" />@else<x-toggle name="is_active" :checked="$u->is_active" submit />@endif
                        </form>
                    </td>
                    <td class="text-right">
                        @unless($me)
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" x-data="{ arm: false }" @submit.prevent="arm ? $el.submit() : (arm = true, setTimeout(() => arm = false, 3000))">@csrf @method('DELETE')<button class="text-sm" :class="arm ? 'font-semibold text-red-600' : 'text-red-500 hover:underline'" x-text="arm ? 'Confirm delete' : 'Delete'"></button></form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
