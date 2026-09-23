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
    $canDownload = $design->is_free || $unlocked;
@endphp

@section('content')
<div x-data="designPage(@js(route('designs.like', $design)), {{ $design->likes }}, @js($gallery), @js($design->slug))" @keydown.escape.window="closeLightbox()" @keydown.arrow-right.window="lightbox >= 0 && next()" @keydown.arrow-left.window="lightbox >= 0 && prev()">
    <x-page-hero :eyebrow="$design->category->name.' · '.$design->roomType->name" :title="$design->title" :subtitle="$design->summary">
        <div class="flex flex-wrap items-center gap-3.5 text-[0.86rem] text-faint">
            <span class="inline-flex items-center gap-[7px]"><x-icon name="user" size="14" class="text-brand" /> {{ $design->designer }}</span>
            @if($design->area_sqm)<span class="size-1 rounded-full bg-brand-dim"></span><span>{{ $design->area_sqm }} m²</span>@endif
            <span class="size-1 rounded-full bg-brand-dim"></span><span>{{ $design->images->count() }} {{ __('ui.design.images') }}</span>
            <span class="size-1 rounded-full bg-brand-dim"></span><span>{{ compact_number($design->views) }} {{ __('ui.design.views') }}</span>
        </div>
    </x-page-hero>

    <section class="shell grid gap-12 pb-24 pt-[clamp(56px,8vw,90px)] lg:grid-cols-[1.35fr_0.65fr]">
        <div>
            {{-- cover in the portrait frame --}}
            <div class="group relative mb-10">
                <div aria-hidden="true" class="pointer-events-none absolute -inset-6 -z-10 rounded-panel bg-[radial-gradient(closest-side,rgb(34_211_238/0.22),transparent_75%)] blur-[38px]"></div>
                <div aria-hidden="true" class="absolute inset-0 -translate-x-3 translate-y-3 rounded-panel border border-brand/40 transition-transform duration-500 group-hover:-translate-x-1.5 group-hover:translate-y-1.5"></div>
                <div class="relative overflow-hidden rounded-panel border border-brand/60 bg-panel shadow-panel">
                    <img src="{{ thumb($cover, 1600) }}" alt="{{ $design->title }}" class="aspect-[16/10] w-full object-cover transition-transform duration-700 group-hover:scale-[1.02]" fetchpriority="high">
                    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-linear-to-t from-canvas/70 via-transparent to-brand/[0.08]"></div>
                    <span aria-hidden="true" class="pointer-events-none absolute left-4 top-4 size-6 border-l-2 border-t-2 border-brand/70"></span>
                    <span aria-hidden="true" class="pointer-events-none absolute bottom-4 right-4 size-6 border-b-2 border-r-2 border-brand/70"></span>
                    <span class="absolute left-5 bottom-5 flex gap-2">
                        @if($design->is_free)<span class="badge badge-free">{{ __('ui.design.free') }}</span>@elseif($unlocked)<span class="badge badge-glass"><x-icon name="unlock" size="11" /> {{ __('ui.design.owned') }}</span>@else<span class="badge badge-glass"><x-icon name="lock" size="11" /> {{ price($design->price) }}</span>@endif
                        @if($design->featured)<span class="badge badge-accent"><x-icon name="sparkles" size="11" /> Pick</span>@endif
                    </span>
                </div>
            </div>

            <span class="eyebrow">{{ __('ui.design.about') }}</span>
            <p class="mt-5 text-base text-muted">{{ $design->description }}</p>
            @if($design->tags)
                <div class="mt-6 flex flex-wrap gap-2.5">@foreach($design->tags as $t)<a href="{{ route('designs.index', ['tag' => $t]) }}" class="tag-dot hover:text-brand-bright">{{ $t }}</a>@endforeach</div>
            @endif
        </div>

        {{-- quick facts / purchase --}}
        <aside class="relative">
            <div class="card-static sticky top-24 px-[26px] py-7">
                <div class="flex items-baseline justify-between"><span class="text-[2rem] font-bold tracking-tight text-brand-bright">{{ price($design->price) }}</span>@if(!$design->is_free)<span class="font-mono text-[0.72rem] text-faint">one-time</span>@endif</div>
                <p class="mt-2 text-[0.88rem] text-muted">{{ $design->is_free ? __('ui.design.free_note') : __('ui.design.paid_note') }}</p>
                <dl class="mt-5 flex flex-col">
                    @foreach([[__('ui.filters.style'), $design->category->name], [__('ui.filters.room'), $design->roomType->name], [__('ui.design.images'), $design->images->count()], ['Designer', $design->designer]] as [$k, $v])
                        <div class="border-t border-brand/[0.13] py-3 first:border-t-0 first:pt-0"><dt class="text-[0.72rem] uppercase tracking-[0.12em] text-faint">{{ $k }}</dt><dd class="mt-1 text-[0.93rem] font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-5 flex flex-col gap-2">
                    @if($canDownload)
                        <a href="#gallery" class="btn-primary w-full"><x-icon name="unlock" size="15" /> {{ $unlocked && !$design->is_free ? __('ui.design.owned') : __('ui.design.gallery') }}</a>
                        <a href="{{ route('designs.download', $design) }}" class="btn-ghost w-full"><x-icon name="download" size="15" /> {{ __('ui.design.download_all') }}</a>
                    @elseif($buyForm)
                        <form method="POST" action="{{ $buyForm }}">@csrf<button class="btn-primary w-full"><x-icon name="lock" size="15" /> {{ __('ui.design.unlock') }}</button></form>
                    @else
                        <a href="{{ $loginUrl }}" class="btn-primary w-full"><x-icon name="lock" size="15" /> {{ __('ui.design.signin') }}</a>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="like" :disabled="liked" class="btn-ghost px-3 disabled:opacity-100" :class="liked ? 'border-brand/60 text-brand-bright' : ''" :aria-pressed="liked" :title="liked ? @js(__('ui.design.liked')) : @js(__('ui.design.like_hint'))">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                            <span x-text="liked ? @js(__('ui.design.liked')) : @js(__('ui.design.like'))"></span>
                            <span class="font-mono text-[0.72rem] text-faint" x-text="likes"></span>
                        </button>
                        <button @click="share" class="btn-ghost px-3" title="Copy link"><x-icon name="share" size="15" /> Share</button>
                    </div>
                </div>
                <p class="mt-5 rounded-lg border border-dashed border-brand/60 bg-brand/[0.06] px-3.5 py-3 font-mono text-[0.74rem] leading-normal text-brand-bright">{{ __('ui.design.like_hint') }}</p>
            </div>
        </aside>
    </section>

    {{-- gallery --}}
    <section id="gallery" class="shell scroll-mt-24 pb-24">
        <x-section-header :eyebrow="$design->images->count().' '.__('ui.design.images')" :title="__('ui.design.gallery')" :subtitle="__('ui.design.gallery_sub')" />
        <div class="grid gap-[22px] sm:grid-cols-2 lg:grid-cols-3">
            @foreach($gallery as $i => $img)
                <article class="card group flex flex-col overflow-hidden">
                    <div class="relative h-[190px] overflow-hidden border-b border-brand/25 {{ $img['url'] ? '' : 'cover-hatch' }}">
                        @if($img['url'])
                            <button @click="openLightbox({{ $i }})" class="block h-full w-full" aria-label="Open {{ $img['title'] }}"><img src="{{ thumb($img['url'], 900) }}" alt="{{ $img['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]"></button>
                            <span class="absolute right-3 top-3 grid size-[34px] -translate-y-1.5 place-items-center rounded-[9px] border border-brand/25 bg-canvas/60 text-brand-bright opacity-0 transition duration-200 group-hover:translate-y-0 group-hover:opacity-100"><x-icon name="arrow-right" size="15" class="-rotate-45" /></span>
                        @else
                            @if($buyForm)<form method="POST" action="{{ $buyForm }}" class="block h-full w-full">@csrf<button class="grid h-full w-full place-items-center">@else<a href="{{ $loginUrl }}" class="grid h-full w-full place-items-center">@endif
                                <span class="flex flex-col items-center gap-2 text-brand-bright"><span class="grid size-12 place-items-center rounded-full border border-brand/60 bg-brand/[0.06] shadow-[0_0_24px_-8px_#22d3ee]"><x-icon name="lock" size="20" /></span><span class="font-mono text-[0.74rem]">{{ __('ui.design.locked') }}</span></span>
                            @if($buyForm)</button></form>@else</a>@endif
                        @endif
                        <span class="absolute left-3 top-3 chip">{{ $img['angle'] }}</span>
                    </div>
                    <div class="flex flex-1 flex-col px-[22px] pb-5 pt-[18px]">
                        <div class="mb-2 flex items-center justify-between gap-3"><h3 class="text-[1.02rem]">{{ $img['title'] }}</h3><span class="shrink-0 font-mono text-[0.72rem] text-faint">{{ $i + 1 }}/{{ $gallery->count() }}</span></div>
                        <p class="text-[0.88rem] text-muted {{ $img['url'] ? '' : 'line-clamp-2' }}">{{ $img['description'] }}</p>
                        <div class="mt-auto pt-4">
                            @if($img['url'])
                                <a href="{{ $img['url'] }}" download="{{ $design->slug }}-{{ $i + 1 }}.jpg" class="inline-flex items-center gap-1.5 text-[0.85rem] font-medium text-brand-bright hover:underline"><x-icon name="download" size="14" /> {{ __('ui.design.download') }}</a>
                            @else
                                <a href="{{ $buyForm ? '#' : $loginUrl }}" @if($buyForm) onclick="event.preventDefault(); this.closest('article').querySelector('form').requestSubmit();" @endif class="inline-flex items-center gap-1.5 text-[0.85rem] font-medium text-brand-bright hover:underline"><x-icon name="lock" size="14" /> {{ __('ui.design.unlock') }} · {{ price($design->price) }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- similar --}}
    <section class="shell pb-24">
        <x-section-header :eyebrow="$design->roomType->name.' · '.$design->category->name" :title="__('ui.design.similar')" :action="__('ui.home.viewall')" :href="route('designs.index', ['roomType' => $design->roomType->slug])" />
        <div class="grid gap-[22px] sm:grid-cols-2 lg:grid-cols-3">@foreach($similar as $d)<x-design-card :design="$d" />@endforeach</div>
    </section>

    {{-- lightbox --}}
    <div x-cloak x-show="lightbox >= 0" x-transition.opacity class="fixed inset-0 z-[100] flex flex-col bg-[#020508]/90 text-fg backdrop-blur-sm" @click="closeLightbox()">
        <div class="flex items-center justify-between p-4">
            <span class="font-mono text-[0.78rem] text-faint" x-text="lightbox >= 0 ? `${lightbox + 1} / ${images.length} · ${images[lightbox].angle}` : ''"></span>
            <button class="grid size-[38px] place-items-center rounded-[10px] border border-brand/25 text-muted transition hover:border-brand/60 hover:text-brand-bright" aria-label="Close"><x-icon name="x" size="18" /></button>
        </div>
        <div class="relative flex flex-1 items-center justify-center px-4" @click.stop>
            <button @click="prev" :disabled="lightbox === 0" class="absolute left-4 grid size-11 place-items-center rounded-[10px] border border-brand/25 bg-canvas/60 text-muted transition hover:border-brand/60 hover:text-brand-bright disabled:opacity-30"><x-icon name="chevron-left" size="20" /></button>
            <template x-if="lightbox >= 0 && images[lightbox].url"><img :src="images[lightbox].url" :alt="images[lightbox].title" class="max-h-[78vh] max-w-full rounded-card border border-brand/40 object-contain shadow-panel"></template>
            <button @click="next" :disabled="lightbox === images.length - 1" class="absolute right-4 grid size-11 place-items-center rounded-[10px] border border-brand/25 bg-canvas/60 text-muted transition hover:border-brand/60 hover:text-brand-bright disabled:opacity-30"><x-icon name="chevron-right" size="20" /></button>
        </div>
        <div class="mx-auto max-w-2xl p-6 text-center" @click.stop><h3 class="text-[1.1rem]" x-text="lightbox >= 0 ? images[lightbox].title : ''"></h3><p class="mt-1 text-[0.88rem] text-muted" x-text="lightbox >= 0 ? images[lightbox].description : ''"></p></div>
    </div>
</div>
@endsection
