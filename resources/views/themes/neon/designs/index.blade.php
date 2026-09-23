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
    <x-page-hero :eyebrow="__('ui.nav.designs')" :title="$title" :subtitle="$designs->total().' '.__('ui.filters.results').'. '.__('ui.home.styles_sub')">
        <form method="GET" action="{{ route('designs.index') }}" class="flex max-w-xl items-center gap-2">
            @foreach(request()->except(['q', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <div class="relative flex-1"><x-icon name="search" size="16" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-faint" /><input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('ui.nav.search') }}" class="input pl-11"></div>
            <button class="btn-primary px-5">{{ __('ui.nav.search') === 'Search designs, styles, rooms…' ? 'Search' : __('ui.filters.title') }}</button>
            @if($filters['q'])<a href="{{ $url(['q' => '']) }}" class="btn-ghost px-4" aria-label="Clear"><x-icon name="x" size="15" /></a>@endif
        </form>
    </x-page-hero>

    <section class="shell pb-24 pt-[clamp(56px,8vw,90px)]">
        {{-- filter pills --}}
        <div class="mb-5 flex flex-wrap items-center gap-2.5" role="tablist" aria-label="{{ __('ui.filters.style') }}">
            <span class="mr-1 font-mono text-[0.7rem] uppercase tracking-[0.18em] text-faint">{{ __('ui.filters.style') }}</span>
            <a href="{{ $url(['category' => '']) }}" class="pill {{ !$filters['category'] ? 'pill-active' : '' }}">{{ __('ui.filters.all') }}</a>
            @foreach($categories as $c)<a href="{{ $toggle('category', $c->slug) }}" class="pill {{ in_array($c->slug, $filters['category']) ? 'pill-active' : '' }}">{{ $c->name }} <span class="font-mono text-[0.7rem] opacity-75">{{ $c->designs_count }}</span></a>@endforeach
        </div>
        <div class="mb-5 flex flex-wrap items-center gap-2.5" role="tablist" aria-label="{{ __('ui.filters.room') }}">
            <span class="mr-1 font-mono text-[0.7rem] uppercase tracking-[0.18em] text-faint">{{ __('ui.filters.room') }}</span>
            <a href="{{ $url(['roomType' => '']) }}" class="pill {{ !$filters['roomType'] ? 'pill-active' : '' }}">{{ __('ui.filters.all') }}</a>
            @foreach($rooms as $r)<a href="{{ $toggle('roomType', $r->slug) }}" class="pill {{ in_array($r->slug, $filters['roomType']) ? 'pill-active' : '' }}">{{ $r->name }} <span class="font-mono text-[0.7rem] opacity-75">{{ $r->designs_count }}</span></a>@endforeach
        </div>
        <div class="mb-9 flex flex-wrap items-center justify-between gap-4 border-t border-brand/15 pt-5">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="mr-1 font-mono text-[0.7rem] uppercase tracking-[0.18em] text-faint">{{ __('ui.filters.price') }}</span>
                @foreach([['', __('ui.filters.all')], ['free', __('ui.filters.free')], ['paid', __('ui.filters.paid')]] as [$v, $l])<a href="{{ $url(['price' => $v]) }}" class="pill {{ $filters['price'] === $v ? 'pill-active' : '' }}">{{ $l }}</a>@endforeach
                @foreach($filters['tag'] as $s)<a href="{{ $toggle('tag', $s) }}" class="pill pill-active">#{{ $s }} <x-icon name="x" size="12" /></a>@endforeach
                @if($activeCount)<a href="{{ route('designs.index', array_filter(['q' => $filters['q']])) }}" class="pill"><x-icon name="x" size="12" /> {{ __('ui.filters.clear') }}</a>@endif
            </div>
            <form method="GET" class="inline-flex items-center gap-2 text-sm">
                @foreach(request()->except(['sort', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <span class="font-mono text-[0.7rem] uppercase tracking-[0.18em] text-faint">{{ __('ui.filters.sort') }}</span>
                <select name="sort" onchange="this.form.submit()" class="input !w-auto !py-2">
                    @foreach(\App\Http\Controllers\DesignController::SORTS as $s)<option value="{{ $s }}" @selected($filters['sort'] === $s)>{{ __('ui.sort.'.$s) }}</option>@endforeach
                </select>
            </form>
        </div>

        @if($designs->count())
            <div class="grid gap-[22px] sm:grid-cols-2 lg:grid-cols-3">
                @foreach($designs as $d)<x-design-card :design="$d" />@endforeach
            </div>
            {{ $designs->links() }}
        @else
            <div class="card-static px-8 py-16 text-center">
                <span class="icon-tile mx-auto mb-4 size-14"><x-icon name="search" size="24" /></span>
                <h3 class="text-[1.3rem]">{{ __('ui.filters.none') }}</h3>
                <a href="{{ route('designs.index') }}" class="btn-ghost mt-6">{{ __('ui.filters.clear') }}</a>
            </div>
        @endif

        @if(count($tags))
            <div class="mt-10 rounded-r-lg border-l-2 border-brand bg-brand/[0.06] px-[22px] py-[18px]">
                <p class="mb-3 font-mono text-[0.7rem] uppercase tracking-[0.18em] text-faint">{{ __('ui.filters.tags') }}</p>
                <div class="flex flex-wrap gap-2">@foreach($tags as $tag => $n)<a href="{{ $toggle('tag', $tag) }}" class="chip {{ in_array($tag, $filters['tag']) ? 'chip-active' : '' }}">#{{ $tag }}</a>@endforeach</div>
            </div>
        @endif
    </section>
@endsection
