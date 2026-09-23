@extends('layouts.app')
@section('title', $design->title)

@php
    $cover = $design->cover;
    $gallery = $design->images->values()->map(fn ($img, $i) => [
        'id' => $img->id, 'angle' => $img->angle, 'title' => $img->title ?: "{$design->title} · {$img->angle}", 'description' => $img->description,
        'url' => ($unlocked || $i === 0) ? $img->url : null,
    ]);
    $buyForm = auth()->check() ? route('checkout.start', $design) : null;
    $loginUrl = route('login', ['next' => route('designs.show', $design)]);
@endphp

@section('content')
<div class="pt-20" x-data="designPage(@js(route('designs.like', $design)), {{ $design->likes }}, @js($gallery), @js($design->slug))" @keydown.escape.window="closeLightbox()" @keydown.arrow-right.window="lightbox >= 0 && next()" @keydown.arrow-left.window="lightbox >= 0 && prev()">
    <section class="container-page">
        <nav class="mb-4 flex flex-wrap items-center gap-1 text-xs text-ink-faint">
            <a href="{{ route('home') }}" class="hover:text-ink">{{ __('ui.nav.home') }}</a><span>/</span>
            <a href="{{ route('designs.index') }}" class="hover:text-ink">{{ __('ui.nav.designs') }}</a><span>/</span>
            <a href="{{ route('designs.index', ['roomType' => $design->roomType->slug]) }}" class="hover:text-ink">{{ $design->roomType->name }}</a><span>/</span>
            <span class="text-ink">{{ $design->title }}</span>
        </nav>
        <div class="relative overflow-hidden rounded-xl3 bg-surface-2">
            <div class="aspect-[16/9] sm:aspect-[21/9]"><img src="{{ thumb($cover, 1800) }}" alt="{{ $design->title }}" class="h-full w-full object-cover" fetchpriority="high"></div>
            <div class="absolute left-4 top-4 flex gap-2">
                @if($design->is_free)<span class="badge badge-free">{{ __('ui.design.free') }}</span>
                @elseif($unlocked)<span class="badge badge-glass"><x-icon name="unlock" size="11" /> {{ __('ui.design.owned') }}</span>
                @else<span class="badge badge-glass"><x-icon name="lock" size="11" /> {{ price($design->price) }}</span>@endif
                @if($design->featured)<span class="badge badge-accent"><x-icon name="sparkles" size="11" /> Pick</span>@endif
            </div>
        </div>
    </section>

    <section class="container-page mt-10 grid gap-10 lg:grid-cols-[1fr_360px]">
        <div>
            <p class="eyebrow mb-2"><a href="{{ route('designs.index', ['category' => $design->category->slug]) }}" class="hover:underline">{{ $design->category->name }}</a> · <a href="{{ route('designs.index', ['roomType' => $design->roomType->slug]) }}" class="hover:underline">{{ $design->roomType->name }}</a></p>
            <h1 class="text-4xl sm:text-5xl">{{ $design->title }}</h1>
            <p class="mt-4 text-lg text-ink-muted">{{ $design->summary }}</p>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-muted">
                <span class="inline-flex items-center gap-1.5"><x-icon name="user" size="15" /> {{ $design->designer }}</span>
                @if($design->area_sqm)<span class="inline-flex items-center gap-1.5"><x-icon name="ruler" size="15" /> {{ $design->area_sqm }} m²</span>@endif
                <span class="inline-flex items-center gap-1.5"><x-icon name="images" size="15" /> {{ $design->images->count() }} {{ __('ui.design.images') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="eye" size="15" /> {{ compact_number($design->views) }} {{ __('ui.design.views') }}</span>
            </div>
            <div class="mt-8 prose-soft"><h2 class="mb-3 text-2xl">{{ __('ui.design.about') }}</h2><p>{{ $design->description }}</p></div>
            @if($design->tags)
                <div class="mt-4 flex flex-wrap gap-2">@foreach($design->tags as $t)<a href="{{ route('designs.index', ['tag' => $t]) }}" class="chip">#{{ $t }}</a>@endforeach</div>
            @endif
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card p-6 shadow-soft">
                <div class="flex items-baseline justify-between"><span class="font-serif text-4xl">{{ price($design->price) }}</span>@if(!$design->is_free)<span class="text-xs text-ink-faint">one-time</span>@endif</div>
                <p class="mt-3 text-sm text-ink-muted">{{ $design->is_free ? __('ui.design.free_note') : __('ui.design.paid_note') }}</p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach(["{$design->images->count()} images in every angle", 'Designer notes for each shot', 'Full-resolution downloads', 'Lifetime access in My Studio'] as $x)
                        <li class="flex items-center gap-2 text-ink-muted"><x-icon name="check" size="14" class="text-accent" /> {{ $x }}</li>
                    @endforeach
                </ul>
                <div class="mt-6 space-y-2">
                    @if($design->is_free || $unlocked)
                        <a href="#gallery" class="btn-primary w-full"><x-icon name="unlock" /> {{ $unlocked && !$design->is_free ? __('ui.design.owned') : __('ui.design.gallery') }}</a>
                        <a href="{{ route('designs.download', $design) }}" class="btn-ghost w-full"><x-icon name="download" size="15" /> {{ __('ui.design.download_all') }}</a>
                        <p class="text-center text-xs text-ink-faint">{{ __('ui.design.download_hint') }}</p>
                    @elseif($buyForm)
                        <form method="POST" action="{{ $buyForm }}">@csrf<button class="btn-primary w-full"><x-icon name="lock" /> {{ __('ui.design.unlock') }}</button></form>
                    @else
                        <a href="{{ $loginUrl }}" class="btn-primary w-full"><x-icon name="lock" /> {{ __('ui.design.signin') }}</a>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="like" :disabled="liked" class="btn-ghost disabled:opacity-100" :class="liked ? 'border-accent text-accent' : ''" :title="liked ? @js(__('ui.design.liked')) : @js(__('ui.design.like_hint'))" :aria-pressed="liked">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                            <span x-text="liked ? @js(__('ui.design.liked')) : @js(__('ui.design.like'))"></span>
                            <span class="rounded-pill bg-surface-2 px-1.5 text-xs text-ink-muted" x-text="likes"></span>
                        </button>
                        <button @click="share" class="btn-ghost" title="Copy link to this design"><x-icon name="share" size="15" /> Share</button>
                    </div>
                    <p class="text-center text-xs text-ink-faint">{{ __('ui.design.like_hint') }}</p>
                </div>
            </div>
        </aside>
    </section>

    {{-- Gallery: every image as a product-style card --}}
    <section id="gallery" class="container-page mt-16 scroll-mt-24">
        <div class="mb-8">
            <p class="eyebrow mb-2">{{ $design->images->count() }} {{ __('ui.design.images') }}</p>
            <h2 class="text-3xl sm:text-4xl">{{ __('ui.design.gallery') }}</h2>
            <p class="mt-2 text-ink-muted">{{ __('ui.design.gallery_sub') }}</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($gallery as $i => $img)
                <article class="card group overflow-hidden">
                    <div class="relative aspect-[4/3] overflow-hidden bg-surface-2">
                        @if($img['url'])
                            <button @click="openLightbox({{ $i }})" class="block h-full w-full"><img src="{{ thumb($img['url'], 900) }}" alt="{{ $img['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]"></button>
                        @else
                            @if($buyForm)<form method="POST" action="{{ $buyForm }}" class="block h-full w-full">@csrf<button class="block h-full w-full">@else<a href="{{ $loginUrl }}" class="block h-full w-full">@endif
                                <img src="{{ thumb($cover, 400) }}" alt="" class="h-full w-full scale-110 object-cover blur-xl">
                                <span class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/35 text-white">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/15 backdrop-blur"><x-icon name="lock" size="20" /></span>
                                    <span class="text-sm font-medium">{{ __('ui.design.locked') }}</span>
                                </span>
                            @if($buyForm)</button></form>@else</a>@endif
                        @endif
                        <span class="badge badge-glass absolute left-3 top-3">{{ $img['angle'] }}</span>
                        <span class="absolute right-3 top-3 rounded-pill bg-black/45 px-2 py-0.5 text-[11px] text-white backdrop-blur">{{ $i + 1 }}/{{ $gallery->count() }}</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-serif text-lg leading-snug">{{ $img['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted {{ $img['url'] ? '' : 'line-clamp-2' }}">{{ $img['description'] }}</p>
                        @if(!$img['url'])
                            <a href="{{ $buyForm ? '#' : $loginUrl }}" @if($buyForm) onclick="event.preventDefault(); this.closest('article').querySelector('form').requestSubmit();" @endif class="mt-4 inline-block text-sm font-medium text-accent hover:underline">{{ __('ui.design.unlock') }} · {{ price($design->price) }}</a>
                        @else
                            <a href="{{ $img['url'] }}" download="{{ $design->slug }}-{{ $i + 1 }}.jpg" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline"><x-icon name="download" size="14" /> {{ __('ui.design.download') }}</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Similar --}}
    <section class="container-page mt-20">
        <div class="mb-8 flex items-end justify-between">
            <div><p class="eyebrow mb-2">{{ $design->roomType->name }} · {{ $design->category->name }}</p><h2 class="text-3xl sm:text-4xl">{{ __('ui.design.similar') }}</h2></div>
            <a href="{{ route('designs.index', ['roomType' => $design->roomType->slug]) }}" class="btn-ghost hidden sm:inline-flex">{{ __('ui.home.viewall') }} <x-icon name="chevron-right" size="15" /></a>
        </div>
        <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($similar as $d)<x-design-card :design="$d" />@endforeach
        </div>
    </section>

    {{-- Lightbox --}}
    <div x-cloak x-show="lightbox >= 0" x-transition.opacity class="fixed inset-0 z-[95] flex flex-col bg-black/95 text-white" @click="closeLightbox()">
        <div class="flex items-center justify-between p-4">
            <span class="text-sm text-white/70" x-text="lightbox >= 0 ? `${lightbox + 1} / ${images.length} · ${images[lightbox].angle}` : ''"></span>
            <button class="rounded-full p-2 hover:bg-white/10" aria-label="Close"><x-icon name="x" size="20" /></button>
        </div>
        <div class="relative flex flex-1 items-center justify-center px-4" @click.stop>
            <button @click="prev" :disabled="lightbox === 0" class="absolute left-4 rounded-full bg-white/10 p-3 hover:bg-white/20 disabled:opacity-30"><x-icon name="chevron-left" size="20" /></button>
            <template x-if="lightbox >= 0 && images[lightbox].url"><img :src="images[lightbox].url" :alt="images[lightbox].title" class="max-h-[78vh] max-w-full rounded-lg object-contain"></template>
            <button @click="next" :disabled="lightbox === images.length - 1" class="absolute right-4 rounded-full bg-white/10 p-3 hover:bg-white/20 disabled:opacity-30"><x-icon name="chevron-right" size="20" /></button>
        </div>
        <div class="mx-auto max-w-2xl p-6 text-center" @click.stop>
            <h3 class="font-serif text-xl" x-text="lightbox >= 0 ? images[lightbox].title : ''"></h3>
            <p class="mt-1 text-sm text-white/70" x-text="lightbox >= 0 ? images[lightbox].description : ''"></p>
        </div>
    </div>
</div>
@endsection
