@extends('layouts.admin')
@section('title', 'Designs')

@section('content')
<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex min-w-[240px] flex-1 items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2 sm:max-w-sm">
        <x-icon name="search" size="15" class="text-ink-faint" />
        <input name="q" value="{{ $q }}" placeholder="Search title, tags, designer…" class="w-full bg-transparent text-sm outline-none">
    </form>
    <div class="ml-auto flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('admin.designs.recompute') }}">@csrf<button class="btn-ghost"><x-icon name="refresh" size="14" /> Recompute trending</button></form>
        <a href="{{ route('admin.designs.create') }}" class="btn-primary"><x-icon name="plus" size="15" /> New design</a>
    </div>
</div>

<div class="card overflow-x-auto">
    @if($designs->count())
        <table class="table-base">
            <thead><tr><th>Design</th><th>Style / Room</th><th>Price</th><th>Images</th><th>Stats</th><th>Published</th><th>Featured</th><th></th></tr></thead>
            <tbody>
                @foreach($designs as $d)
                    <tr class="hover:bg-surface-2/50">
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ thumb($d->cover_image, 160) }}" alt="" class="h-12 w-16 shrink-0 rounded-lg object-cover">
                                <div class="min-w-0"><a href="{{ route('admin.designs.edit', $d) }}" class="block max-w-[260px] truncate font-medium hover:text-accent">{{ $d->title }}</a><p class="text-xs text-ink-faint">{{ $d->designer }} · /{{ $d->slug }}</p></div>
                            </div>
                        </td>
                        <td class="text-ink-muted">{{ $d->category?->name }}<br><span class="text-xs">{{ $d->roomType?->name }}</span></td>
                        <td>@if($d->is_free)<span class="badge badge-free">Free</span>@else{{ price($d->price) }}@endif</td>
                        <td>{{ $d->images_count }}</td>
                        <td class="text-xs text-ink-muted">{{ compact_number($d->views) }} views<br>{{ $d->purchases }} sold · {{ $d->likes }} likes</td>
                        <td><form method="POST" action="{{ route('admin.designs.toggle', $d) }}">@csrf @method('PATCH')<input type="hidden" name="field" value="published"><x-toggle name="on" :checked="$d->published" submit /></form></td>
                        <td><form method="POST" action="{{ route('admin.designs.toggle', $d) }}">@csrf @method('PATCH')<input type="hidden" name="field" value="featured"><button class="rounded-full p-1.5 {{ $d->featured ? 'text-amber-500' : 'text-ink-faint hover:text-ink' }}" aria-label="Toggle featured"><x-icon name="star" size="16" :fill="$d->featured ? 'currentColor' : 'none'" /></button></form></td>
                        <td>
                            <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                <a href="{{ route('designs.show', $d) }}" target="_blank" class="text-ink-faint hover:text-ink" aria-label="Open on site"><x-icon name="external" size="15" /></a>
                                <a href="{{ route('admin.designs.edit', $d) }}" class="text-ink-faint hover:text-ink" aria-label="Edit"><x-icon name="pencil" size="15" /></a>
                                <form method="POST" action="{{ route('admin.designs.destroy', $d) }}" x-data="{ arm: false }" @submit.prevent="arm ? $el.submit() : (arm = true, setTimeout(() => arm = false, 3000))">
                                    @csrf @method('DELETE')
                                    <button class="text-sm" :class="arm ? 'font-semibold text-red-600' : 'text-red-500 hover:underline'" x-text="arm ? 'Confirm delete' : 'Delete'"></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="py-14 text-center text-sm text-ink-muted">No designs match.</p>
    @endif
</div>
{{ $designs->links() }}
@endsection
