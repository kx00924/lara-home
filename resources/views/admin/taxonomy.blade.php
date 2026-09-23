@extends('layouts.admin')
@section('title', $label['many'])

@php
    $base = $kind === 'categories' ? 'admin.categories' : 'admin.room-types';
    $empty = ['name' => '', 'description' => '', 'image' => '', 'icon' => 'home', 'sort_order' => count($items) + 1, 'is_active' => true];
@endphp

@section('content')
<div x-data="{ editing: null, form: @js($empty), open(item) { this.editing = item ? item.id : 'new'; this.form = item ? { ...item } : @js($empty); } }" @keydown.escape.window="editing = null">
    <p class="mb-5 text-sm text-ink-muted">{{ $label['hint'] }}</p>
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <form method="GET" class="flex min-w-[240px] flex-1 items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2 sm:max-w-sm"><x-icon name="search" size="15" class="text-ink-faint" /><input name="q" value="{{ $q }}" placeholder="Search {{ strtolower($label['many']) }}…" class="w-full bg-transparent text-sm outline-none"></form>
        <button @click="open(null)" class="btn-primary ml-auto"><x-icon name="plus" size="15" /> New {{ $label['one'] }}</button>
    </div>

    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Name</th><th>Description</th><th>Designs</th><th>Order</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><div class="flex items-center gap-3">@if($item->image)<img src="{{ thumb($item->image, 120) }}" alt="" class="h-10 w-14 rounded-lg object-cover">@else<span class="h-10 w-14 rounded-lg bg-surface-2"></span>@endif<div><p class="font-medium">{{ $item->name }}</p><p class="text-xs text-ink-faint">/{{ $item->slug }}</p></div></div></td>
                        <td class="max-w-md text-ink-muted"><p class="line-clamp-2 text-xs">{{ $item->description }}</p></td>
                        <td>{{ $item->designs_count }}</td>
                        <td>{{ $item->sort_order }}</td>
                        <td>
                            <form method="POST" action="{{ route($base.'.update', $item) }}">@csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $item->name }}"><input type="hidden" name="description" value="{{ $item->description }}"><input type="hidden" name="image" value="{{ $item->image }}"><input type="hidden" name="icon" value="{{ $item->icon ?? '' }}"><input type="hidden" name="sort_order" value="{{ $item->sort_order }}">
                                <x-toggle name="is_active" :checked="$item->is_active" submit />
                            </form>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-3">
                                <button @click="open(@js($item->only('id', 'name', 'description', 'image', 'icon', 'sort_order', 'is_active')))" class="text-ink-faint hover:text-ink" aria-label="Edit"><x-icon name="pencil" size="15" /></button>
                                <form method="POST" action="{{ route($base.'.destroy', $item) }}" x-data="{ arm: false }" @submit.prevent="arm ? $el.submit() : (arm = true, setTimeout(() => arm = false, 3000))">@csrf @method('DELETE')<button class="text-sm" :class="arm ? 'font-semibold text-red-600' : 'text-red-500 hover:underline'" x-text="arm ? 'Confirm delete' : 'Delete'"></button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-14 text-center text-sm text-ink-muted">Nothing here yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    <div x-cloak x-show="editing" x-transition.opacity class="fixed inset-0 z-[90] flex items-end justify-center bg-black/50 p-0 backdrop-blur-sm sm:items-center sm:p-6" @click.self="editing = null">
        <form :action="editing === 'new' ? @js(route($base.'.store')) : @js(url('admin/'.$kind)) + '/' + editing" method="POST" class="max-h-[92vh] w-full overflow-y-auto rounded-t-xl3 bg-surface p-6 shadow-lift sm:max-w-lg sm:rounded-xl3">
            @csrf
            <template x-if="editing !== 'new'"><input type="hidden" name="_method" value="PUT"></template>
            <div class="mb-4 flex items-center justify-between"><h3 class="text-xl" x-text="editing === 'new' ? 'New {{ $label['one'] }}' : 'Edit {{ $label['one'] }}'"></h3><button type="button" @click="editing = null" class="rounded-full p-2 text-ink-faint hover:bg-surface-2 hover:text-ink"><x-icon name="x" size="18" /></button></div>
            <div class="space-y-4">
                <x-field label="Name"><input name="name" x-model="form.name" required class="input"></x-field>
                <x-field label="Description"><textarea name="description" x-model="form.description" class="input"></textarea></x-field>
                <x-field label="Image URL"><input name="image" x-model="form.image" class="input"></x-field>
                <template x-if="form.image"><img :src="form.image" alt="" class="aspect-[3/1] w-full rounded-xl2 object-cover"></template>
                <div class="grid grid-cols-2 gap-4">
                    <x-field label="Sort order"><input type="number" name="sort_order" x-model="form.sort_order" class="input"></x-field>
                    @if($kind === 'room-types')<x-field label="Icon name" hint="lucide icon id"><input name="icon" x-model="form.icon" class="input"></x-field>@endif
                </div>
                <label class="flex items-center justify-between text-sm"><span>Active</span><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-5 w-5 accent-accent"></label>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="editing = null" class="btn-ghost">Cancel</button><button class="btn-primary">Save</button></div>
            </div>
        </form>
    </div>
</div>
@endsection
