@extends('layouts.app')
@section('title', $title)

@php
    $activeCount = count($filters['category']) + count($filters['roomType']) + count($filters['tag']) + ($filters['price'] ? 1 : 0) + ($filters['featured'] ? 1 : 0);
    $url = fn (array $patch) => route('designs.index', array_filter(array_merge(request()->except('page'), $patch), fn ($v) => $v !== '' && $v !== null && $v !== []));
    $toggle = function (string $key, string $slug) use ($filters, $url) {
        $cur = $filters[$key];
        $next = in_array($slug, $cur) ? array_values(array_diff($cur, [$slug])) : [...$cur, $slug];
        return $url([$key => implode(',', $next)]);
    };
@endphp

@section('content')
<div class="container-page pt-28" x-data="{ drawer: false }">
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="eyebrow mb-2">{{ __('ui.nav.designs') }}</p>
            <h1 class="text-4xl sm:text-5xl">{{ $title }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ $designs->total() }} {{ __('ui.filters.results') }}</p>
        </div>
        <form method="GET" action="{{ route('designs.index') }}" class="flex w-full max-w-md items-center gap-2 rounded-pill border border-line bg-surface px-4 py-2">
            @foreach(request()->except(['q', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <x-icon name="search" class="text-ink-faint" />
            <input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('ui.nav.search') }}" class="w-full bg-transparent text-sm outline-none">
            @if($filters['q'])<a href="{{ $url(['q' => '']) }}" class="text-ink-faint hover:text-ink" aria-label="Clear"><x-icon name="x" size="14" /></a>@endif
        </form>
    </div>

    <div class="grid gap-10 lg:grid-cols-[260px_1fr]">
        <aside class="hidden lg:block"><div class="sticky top-24">@include('designs.partials.filters')</div></aside>

        <div>
            <div class="mb-6 flex items-center justify-between gap-3">
                <button @click="drawer = true" class="btn-ghost lg:hidden"><x-icon name="sliders" size="15" /> {{ __('ui.filters.title') }} @if($activeCount)<span class="rounded-pill bg-accent px-1.5 text-[10px] text-accent-fg">{{ $activeCount }}</span>@endif</button>
                <div class="hidden flex-wrap gap-2 lg:flex">
                    @foreach($filters['category'] as $s)<a href="{{ $toggle('category', $s) }}" class="chip chip-active">{{ $categories->firstWhere('slug', $s)?->name ?? $s }} <x-icon name="x" size="12" /></a>@endforeach
                    @foreach($filters['roomType'] as $s)<a href="{{ $toggle('roomType', $s) }}" class="chip chip-active">{{ $rooms->firstWhere('slug', $s)?->name ?? $s }} <x-icon name="x" size="12" /></a>@endforeach
                    @foreach($filters['tag'] as $s)<a href="{{ $toggle('tag', $s) }}" class="chip chip-active">#{{ $s }} <x-icon name="x" size="12" /></a>@endforeach
                    @if($filters['price'])<a href="{{ $url(['price' => '']) }}" class="chip chip-active">{{ $filters['price'] === 'free' ? __('ui.filters.free') : __('ui.filters.paid') }} <x-icon name="x" size="12" /></a>@endif
                    @if($filters['featured'])<a href="{{ $url(['featured' => '']) }}" class="chip chip-active">{{ __('ui.home.featured') }} <x-icon name="x" size="12" /></a>@endif
                </div>
                <form method="GET" class="relative ml-auto inline-flex items-center gap-2 text-sm">
                    @foreach(request()->except(['sort', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <span class="hidden text-ink-muted sm:inline">{{ __('ui.filters.sort') }}</span>
                    <span class="relative">
                        <select name="sort" onchange="this.form.submit()" class="input appearance-none !rounded-pill !py-2 !pr-9">
                            @foreach(\App\Http\Controllers\DesignController::SORTS as $s)<option value="{{ $s }}" @selected($filters['sort'] === $s)>{{ __('ui.sort.'.$s) }}</option>@endforeach
                        </select>
                        <x-icon name="chevron-down" size="14" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-faint" />
                    </span>
                </form>
            </div>

            @if($designs->count())
                <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($designs as $d)<x-design-card :design="$d" />@endforeach
                </div>
                {{ $designs->links() }}
            @else
                <x-empty-state icon="search" :title="__('ui.filters.none')" text="Try removing a filter or searching for a different word.">
                    <a href="{{ route('designs.index') }}" class="btn-primary">{{ __('ui.filters.clear') }}</a>
                </x-empty-state>
            @endif
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div x-cloak x-show="drawer" x-transition.opacity class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm lg:hidden" @click.self="drawer = false" @keydown.escape.window="drawer = false">
        <aside class="absolute left-0 top-0 flex h-full w-full max-w-sm flex-col bg-surface shadow-lift">
            <div class="flex items-center justify-between border-b border-line p-5"><h3 class="text-xl">{{ __('ui.filters.title') }}</h3><button @click="drawer = false" class="rounded-full p-2 hover:bg-surface-2"><x-icon name="x" size="18" /></button></div>
            <div class="flex-1 overflow-y-auto p-5">@include('designs.partials.filters')</div>
        </aside>
    </div>
</div>
@endsection
