@extends('layouts.app')

@php
    $freeCount = $trending->where('price', 0)->count();
@endphp

@section('content')
    {{-- hero --}}
    <section class="pb-10 pt-36">
        <div class="shell grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr]">
            <div class="group relative order-first animate-rise lg:order-last" style="animation-delay:200ms">
                <div aria-hidden="true" class="pointer-events-none absolute -inset-8 -z-10 rounded-panel bg-[radial-gradient(closest-side,rgb(34_211_238/0.26),transparent_74%)] blur-[42px]"></div>
                <div aria-hidden="true" class="absolute inset-0 -translate-x-3 translate-y-3 rounded-panel border border-brand/40 transition-transform duration-500 ease-out group-hover:-translate-x-1.5 group-hover:translate-y-1.5"></div>
                <div class="relative overflow-hidden rounded-panel border border-brand/60 bg-panel shadow-panel">
                    <img src="{{ thumb($site['heroImage'], 1400) }}" alt="" class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03] sm:aspect-[5/4]" fetchpriority="high">
                    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-linear-to-t from-canvas/70 via-transparent to-brand/[0.08]"></div>
                    <span aria-hidden="true" class="pointer-events-none absolute left-4 top-4 size-6 border-l-2 border-t-2 border-brand/70"></span>
                    <span aria-hidden="true" class="pointer-events-none absolute bottom-4 right-4 size-6 border-b-2 border-r-2 border-brand/70"></span>
                    <div class="absolute inset-x-5 bottom-5 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="font-mono text-[0.7rem] uppercase tracking-[0.18em] text-brand-bright">{{ __('ui.home.featured') }}</p>
                            <p class="mt-1 text-[1.05rem] font-semibold">{{ $featured->first()?->title ?? $trending->first()?->title }}</p>
                        </div>
                        @if($featured->first() ?? $trending->first())
                            <a href="{{ route('designs.show', $featured->first() ?? $trending->first()) }}" class="grid size-11 place-items-center rounded-[10px] border border-brand/40 bg-canvas/60 text-brand-bright backdrop-blur transition hover:border-brand hover:bg-brand/10" aria-label="Open featured design"><x-icon name="arrow-right" size="18" class="-rotate-45" /></a>
                        @endif
                    </div>
                </div>
                <span class="absolute -bottom-4 left-6 rounded-full border border-brand/60 bg-canvas/90 px-4 py-2 font-mono text-[0.75rem] text-brand-bright shadow-[0_10px_30px_-14px_#22d3ee] backdrop-blur-sm">{{ $site['statDesigns'] }} {{ mb_strtolower(__('ui.hero.stats_designs')) }} · {{ $freeCount }}+ {{ mb_strtolower(__('ui.filters.free')) }}</span>
            </div>

            <div>
                <span class="inline-flex items-center gap-2.5 rounded-full border border-brand/25 bg-brand/[0.06] px-[15px] py-[7px] text-[0.78rem] font-medium tracking-wide text-brand-bright animate-rise"><i class="size-[7px] animate-pulse-ring rounded-full bg-brand"></i> {{ $site['tagline'] }}</span>
                <h1 class="mt-5 text-[clamp(2.3rem,5.2vw,3.7rem)] animate-rise" style="animation-delay:60ms">
                    <span class="bg-linear-120 from-brand-bright via-brand to-[#a5f3fc] bg-clip-text text-transparent">{{ $site['heroTitle'] }}</span>
                </h1>
                <p class="mt-5 max-w-[540px] text-[1.05rem] text-muted animate-rise" style="animation-delay:140ms">{{ $site['heroSubtitle'] }}</p>
                <div class="mt-5 flex flex-wrap items-center gap-3.5 text-[0.86rem] text-faint animate-rise" style="animation-delay:200ms">
                    <span class="inline-flex items-center gap-[7px]"><x-icon name="layout-grid" size="14" class="text-brand" /> {{ $categories->count() }} {{ __('ui.nav.styles') }}</span>
                    <span class="size-1 rounded-full bg-brand-dim"></span>
                    <span>{{ $rooms->count() }} {{ __('ui.nav.rooms') }}</span>
                    <span class="size-1 rounded-full bg-brand-dim"></span>
                    <span>{{ $site['statDesigns'] }} {{ __('ui.hero.stats_designs') }}</span>
                </div>
                <div class="mt-7 max-w-[540px] animate-rise" style="animation-delay:240ms"><x-search-box large /></div>
                <div class="mt-6 flex flex-wrap gap-3.5 animate-rise" style="animation-delay:280ms">
                    <a class="btn-primary" href="{{ route('designs.index') }}">{{ $site['heroCtaText'] }} <x-icon name="arrow-right" size="15" /></a>
                    <a class="btn-ghost" href="{{ route('designs.index', ['price' => 'free']) }}">{{ __('ui.hero.browse') }}</a>
                </div>
            </div>
        </div>
    </section>

    {{-- stats --}}
    <section class="shell mt-14 grid grid-cols-2 gap-[18px] lg:grid-cols-4">
        @foreach([[$site['statDesigns'], __('ui.hero.stats_designs')], [$site['statDesigners'], __('ui.hero.stats_designers')], [$site['statCustomers'], __('ui.hero.stats_customers')], [$categories->count().' / '.$rooms->count(), __('ui.nav.styles').' / '.__('ui.nav.rooms')]] as $i => [$v, $l])
            <div class="card flex flex-col gap-1.5 px-[22px] py-6 animate-rise" style="animation-delay:{{ $i * 70 }}ms">
                <strong class="text-[clamp(1.7rem,3.4vw,2.3rem)] font-bold tracking-tight text-brand-bright">{{ $v }}</strong>
                <span class="text-[0.85rem] text-muted">{{ $l }}</span>
            </div>
        @endforeach
    </section>

    {{-- styles carousel --}}
    <section class="shell pb-24 pt-[clamp(72px,10vw,120px)]" x-data="carousel(6000)" @mouseenter="paused = true" @mouseleave="paused = false" @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()">
        <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-[660px]"><span class="eyebrow">{{ __('ui.nav.styles') }}</span><h2 class="mt-3.5 text-[clamp(1.9rem,4vw,2.7rem)]">{{ __('ui.home.styles') }}</h2><p class="mt-3.5 text-[1.02rem] text-muted">{{ __('ui.home.styles_sub') }}</p></div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('designs.index') }}" class="btn-ghost px-4 py-2.5 text-[0.85rem]">{{ __('ui.home.viewall') }}</a>
                <button type="button" @click="prev()" :disabled="atStart" class="grid size-11 place-items-center rounded-[10px] border border-brand/25 text-muted transition hover:border-brand/60 hover:text-brand-bright disabled:opacity-40" aria-label="Previous"><x-icon name="chevron-left" size="18" /></button>
                <button type="button" @click="next()" :disabled="atEnd" class="grid size-11 place-items-center rounded-[10px] border border-brand/25 text-muted transition hover:border-brand/60 hover:text-brand-bright disabled:opacity-40" aria-label="Next"><x-icon name="chevron-right" size="18" /></button>
            </div>
        </div>
        <div x-ref="track" class="-mx-6 flex snap-x snap-mandatory gap-[18px] overflow-x-auto scroll-smooth px-6 pb-2 scrollbar-none sm:mx-0 sm:px-0" tabindex="0" aria-roledescription="carousel">
            @foreach($categories as $c)
                <a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="card group w-[72vw] shrink-0 snap-start overflow-hidden sm:w-[calc((100%-18px)/2)] lg:w-[calc((100%-54px)/4)]">
                    <div class="relative h-[200px] overflow-hidden border-b border-brand/25">
                        <img src="{{ thumb($c->image, 600) }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-linear-to-t from-canvas/85 via-transparent to-brand/[0.06]"></div>
                        <span aria-hidden="true" class="pointer-events-none absolute left-3 top-3 size-5 border-l-2 border-t-2 border-brand/70"></span>
                        <span aria-hidden="true" class="pointer-events-none absolute bottom-3 right-3 size-5 border-b-2 border-r-2 border-brand/70"></span>
                    </div>
                    <div class="px-5 pb-5 pt-4">
                        <div class="mb-2 flex items-center justify-between"><h3 class="text-[1.1rem]">{{ $c->name }}</h3><span class="font-mono text-[0.72rem] text-faint">{{ $c->designs_count }}</span></div>
                        <p class="line-clamp-2 text-[0.85rem] text-muted">{{ $c->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-5 flex items-center justify-center gap-1.5" role="tablist">
            <template x-for="i in pages" :key="i"><button type="button" @click="goTo(i - 1)" class="h-1.5 rounded-full transition-all" :class="index === i - 1 ? 'w-6 bg-brand shadow-[0_0_10px_#22d3ee]' : 'w-1.5 bg-brand/25 hover:bg-brand/50'" role="tab" :aria-selected="index === i - 1"></button></template>
        </div>
    </section>

    {{-- trending --}}
    <section class="shell pb-24">
        <x-section-header eyebrow="Popular" :title="__('ui.home.trending')" :subtitle="__('ui.home.trending_sub')" :action="__('ui.home.viewall')" :href="route('designs.index', ['sort' => 'trending'])" />
        <div class="grid gap-[22px] sm:grid-cols-2 lg:grid-cols-4">
            @foreach($trending as $d)<x-design-card :design="$d" />@endforeach
        </div>
    </section>

    {{-- rooms --}}
    <section class="shell pb-24">
        <x-section-header :eyebrow="__('ui.nav.rooms')" :title="__('ui.home.rooms')" :subtitle="__('ui.home.rooms_sub')" />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($rooms as $r)
                <a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="card flex items-center gap-4 px-[18px] py-4">
                    <img src="{{ thumb($r->image, 200) }}" alt="" loading="lazy" class="size-[46px] shrink-0 rounded-xl border border-brand/25 object-cover">
                    <span class="flex min-w-0 flex-col"><strong class="truncate text-[0.93rem] font-medium">{{ $r->name }}</strong><span class="font-mono text-[0.71rem] uppercase tracking-[0.14em] text-faint">{{ $r->designs_count }} designs</span></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- featured --}}
    <section class="shell pb-24">
        <x-section-header eyebrow="Curated" :title="__('ui.home.featured')" :subtitle="__('ui.home.featured_sub')" :action="__('ui.home.viewall')" :href="route('designs.index', ['featured' => 1])" />
        <div class="grid gap-[22px] md:grid-cols-3">
            @foreach($featured as $d)<x-design-card :design="$d" size="lg" />@endforeach
        </div>
    </section>

    {{-- how it works --}}
    <section class="shell pb-24">
        <x-section-header eyebrow="Simple" :title="__('ui.home.how')" />
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([['compass', __('ui.home.how1'), __('ui.home.how1_d')], ['unlock', __('ui.home.how2'), __('ui.home.how2_d')], ['hammer', __('ui.home.how3'), __('ui.home.how3_d')]] as $i => [$icon, $title, $text])
                <div class="card px-[26px] py-[30px]">
                    <span class="icon-tile mb-5 size-[50px]"><x-icon :name="$icon" size="22" /></span>
                    <p class="mb-2 font-mono text-[0.72rem] text-faint">0{{ $i + 1 }}</p>
                    <h3 class="mb-2.5 text-[1.14rem]">{{ $title }}</h3>
                    <p class="text-[0.93rem] text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- testimonials --}}
    <section class="shell pb-24">
        <x-section-header eyebrow="References" title="What clients say" />
        <div class="grid gap-5 md:grid-cols-3">
            @foreach([['Mina P.', 'Seoul', 'I unlocked the Modern Hanok living room and handed the gallery straight to my contractor. Every angle answered a question before he asked it.'], ['Kenji W.', 'Osaka', 'The Japandi collection is the calmest thing on the internet. The free designs alone were enough to plan our bedroom.'], ['Li W.', 'Shanghai', 'Filters by room, style and colour made it easy to find a whole-apartment concept in an evening.']] as [$name, $city, $text])
                <figure class="card px-7 py-[30px]">
                    <p class="text-base italic">&ldquo;{{ $text }}&rdquo;</p>
                    <figcaption class="mt-5 flex flex-col border-t border-brand/15 pt-[18px] text-[0.88rem]"><strong>{{ $name }}</strong><span class="text-[0.8rem] text-muted">{{ $city }}</span></figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- cta --}}
    <section class="shell">
        <div class="mb-[90px] flex flex-wrap items-center justify-between gap-7 rounded-panel border border-brand/60 bg-linear-135 from-brand/[0.09] to-panel/60 px-10 py-[42px] shadow-[inset_0_0_60px_-30px_#22d3ee]">
            <div>
                <h2 class="max-w-[560px] text-[clamp(1.4rem,3vw,1.9rem)]">{{ __('ui.home.newsletter') }}</h2>
                <p class="mt-3 max-w-[560px] text-[0.95rem] text-muted">{{ __('ui.home.newsletter_sub') }}</p>
            </div>
            <form method="POST" action="{{ route('newsletter') }}" class="flex w-full max-w-md gap-2">
                @csrf
                <input type="email" name="email" required placeholder="you@example.com" class="input">
                <button class="btn-primary shrink-0">{{ __('ui.home.newsletter_cta') }}</button>
            </form>
        </div>
    </section>
@endsection
