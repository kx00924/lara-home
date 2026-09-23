@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="relative flex min-h-[92vh] items-end overflow-hidden text-white">
        <img src="{{ $site['heroImage'] }}" alt="" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-black/25"></div>
        <div class="container-page relative pb-16 pt-40 sm:pb-24">
            <p class="eyebrow mb-4 !text-white/80 animate-rise">{{ $site['tagline'] }}</p>
            <h1 class="max-w-4xl text-balance text-5xl font-medium leading-[1.05] sm:text-7xl animate-rise" style="animation-delay:80ms">{{ $site['heroTitle'] }}</h1>
            <p class="mt-6 max-w-2xl text-balance text-base text-white/80 sm:text-lg animate-rise" style="animation-delay:160ms">{{ $site['heroSubtitle'] }}</p>
            <div class="mt-8 max-w-2xl animate-rise" style="animation-delay:240ms"><x-search-box large /></div>
            <div class="mt-6 flex flex-wrap gap-3 animate-rise" style="animation-delay:320ms">
                <a href="{{ route('designs.index') }}" class="btn bg-white text-neutral-900 hover:bg-white/90">{{ $site['heroCtaText'] }} <x-icon name="arrow-right" /></a>
                <a href="{{ route('designs.index', ['price' => 'free']) }}" class="btn border border-white/40 bg-white/10 text-white backdrop-blur hover:bg-white/20">{{ __('ui.hero.browse') }}</a>
            </div>
            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/20 pt-6 animate-rise" style="animation-delay:400ms">
                @foreach([[$site['statDesigns'], __('ui.hero.stats_designs')], [$site['statDesigners'], __('ui.hero.stats_designers')], [$site['statCustomers'], __('ui.hero.stats_customers')]] as [$v, $l])
                    <div><dd class="font-serif text-3xl">{{ $v }}</dd><dt class="text-xs uppercase tracking-wider text-white/70">{{ $l }}</dt></div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Styles --}}
    <section class="container-page pt-20" x-data="carousel(6000)" @mouseenter="paused = true" @mouseleave="paused = false" @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <p class="eyebrow mb-2">{{ __('ui.nav.styles') }}</p>
                <h2 class="text-3xl font-medium sm:text-4xl">{{ __('ui.home.styles') }}</h2>
                <p class="mt-2 text-ink-muted">{{ __('ui.home.styles_sub') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('designs.index') }}" class="btn-ghost">{{ __('ui.home.viewall') }} <x-icon name="chevron-right" /></a>
                <button type="button" @click="prev()" :disabled="atStart" class="btn-ghost h-11 w-11 !p-0" aria-label="Previous styles"><x-icon name="chevron-left" size="18" /></button>
                <button type="button" @click="next()" :disabled="atEnd" class="btn-ghost h-11 w-11 !p-0" aria-label="Next styles"><x-icon name="chevron-right" size="18" /></button>
            </div>
        </div>

        <div class="relative">
            <div x-ref="track" class="-mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 pb-2 scrollbar-none sm:mx-0 sm:px-0" tabindex="0" aria-roledescription="carousel" aria-label="{{ __('ui.home.styles') }}">
                @foreach($categories as $i => $c)
                    <a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="group relative w-[72vw] shrink-0 snap-start overflow-hidden rounded-card sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-3rem)/4)] xl:w-[calc((100%-4rem)/5)]" aria-roledescription="slide">
                        <div class="aspect-[3/4]"><img src="{{ thumb($c->image, 600) }}" alt="{{ $c->name }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/15 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <h3 class="text-2xl leading-tight">{{ $c->name }}</h3>
                            <p class="mt-1 line-clamp-2 text-xs text-white/75">{{ $c->description }}</p>
                            <p class="mt-3 inline-flex items-center gap-1 text-xs font-medium uppercase tracking-wider text-white/90">{{ $c->designs_count }} designs <x-icon name="arrow-right" size="12" class="transition group-hover:translate-x-1" /></p>
                        </div>
                    </a>
                @endforeach
            </div>
            {{-- edge fades hint that the track continues --}}
            <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-10 bg-gradient-to-r from-canvas to-transparent transition-opacity sm:block" :class="atStart ? 'opacity-0' : 'opacity-100'"></div>
            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-10 bg-gradient-to-l from-canvas to-transparent transition-opacity sm:block" :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>
        </div>

        <div class="mt-5 flex items-center justify-center gap-1.5" role="tablist" aria-label="Carousel position">
            <template x-for="i in pages" :key="i">
                <button type="button" @click="goTo(i - 1)" class="h-1.5 rounded-pill transition-all" :class="index === i - 1 ? 'w-6 bg-ink' : 'w-1.5 bg-ink/25 hover:bg-ink/50'" :aria-label="`Go to position ${i}`" :aria-selected="index === i - 1" role="tab"></button>
            </template>
        </div>
    </section>

    {{-- Trending --}}
    <section class="container-page pt-20">
        <x-section-header eyebrow="Popular" :title="__('ui.home.trending')" :subtitle="__('ui.home.trending_sub')" :action="__('ui.home.viewall')" :href="route('designs.index', ['sort' => 'trending'])" />
        <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($trending as $d)<x-design-card :design="$d" />@endforeach
        </div>
    </section>

    {{-- Rooms --}}
    <section class="container-page pt-20">
        <x-section-header :eyebrow="__('ui.nav.rooms')" :title="__('ui.home.rooms')" :subtitle="__('ui.home.rooms_sub')" />
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($rooms as $i => $r)
                <a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="group relative overflow-hidden rounded-card {{ $i === 0 ? 'col-span-2 row-span-2' : '' }}">
                    <div class="{{ $i === 0 ? 'h-full min-h-[280px]' : 'aspect-square' }}"><img src="{{ thumb($r->image, $i === 0 ? 1000 : 500) }}" alt="{{ $r->name }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between text-white"><h3 class="{{ $i === 0 ? 'text-2xl' : 'text-base' }}">{{ $r->name }}</h3><span class="text-[11px] text-white/70">{{ $r->designs_count }}</span></div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured --}}
    <section class="container-page pt-20">
        <x-section-header eyebrow="Curated" :title="__('ui.home.featured')" :subtitle="__('ui.home.featured_sub')" :action="__('ui.home.viewall')" :href="route('designs.index', ['featured' => 1])" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach($featured as $d)<x-design-card :design="$d" size="lg" />@endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="container-page pt-24">
        <div class="rounded-xl3 bg-surface-2 px-6 py-12 sm:px-12">
            <x-section-header eyebrow="Simple" :title="__('ui.home.how')" center />
            <div class="grid gap-8 md:grid-cols-3">
                @foreach([['compass', __('ui.home.how1'), __('ui.home.how1_d')], ['unlock', __('ui.home.how2'), __('ui.home.how2_d')], ['hammer', __('ui.home.how3'), __('ui.home.how3_d')]] as $i => [$icon, $title, $text])
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent text-accent-fg"><x-icon :name="$icon" size="24" /></div>
                        <p class="eyebrow mb-1">0{{ $i + 1 }}</p>
                        <h3 class="text-2xl">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="container-page pt-20">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach([
                ['Mina P.', 'Seoul', 'I unlocked the Modern Hanok living room and handed the gallery straight to my contractor. Every angle answered a question before he asked it.'],
                ['Kenji W.', 'Osaka', 'The Japandi collection is the calmest thing on the internet. The free designs alone were enough to plan our bedroom.'],
                ['Li W.', 'Shanghai', 'Filters by room, style and colour made it easy to find a whole-apartment concept in an evening.'],
            ] as [$name, $city, $text])
                <figure class="card p-6">
                    <x-icon name="quote" size="20" class="text-accent" />
                    <blockquote class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $text }}</blockquote>
                    <figcaption class="mt-4 text-sm font-medium">{{ $name }} <span class="text-ink-faint">· {{ $city }}</span></figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- Newsletter --}}
    <section class="container-page pt-20">
        <div class="relative overflow-hidden rounded-xl3 bg-ink px-6 py-14 text-canvas sm:px-12">
            <div class="relative z-10 grid items-center gap-8 md:grid-cols-2">
                <div><h2 class="text-3xl sm:text-4xl">{{ __('ui.home.newsletter') }}</h2><p class="mt-2 text-canvas/70">{{ __('ui.home.newsletter_sub') }}</p></div>
                <form method="POST" action="{{ route('newsletter') }}" class="flex gap-2">
                    @csrf
                    <input type="email" name="email" required placeholder="you@example.com" class="w-full rounded-pill border border-canvas/20 bg-canvas/10 px-5 py-3 text-sm text-canvas placeholder:text-canvas/50 outline-none focus:border-canvas/50">
                    <button class="btn-primary shrink-0">{{ __('ui.home.newsletter_cta') }}</button>
                </form>
            </div>
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-accent/30 blur-3xl"></div>
        </div>
    </section>
@endsection
