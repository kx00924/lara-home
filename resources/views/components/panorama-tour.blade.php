@props(['tour', 'design', 'unlocked', 'cover', 'buyForm' => null, 'loginUrl' => null])
{{-- 360° tour: one viewer per floor, each switching between that floor's panoramas. Shared by all themes. --}}
@php
    // Admins also see floors that have no panoramas yet, so the floor layout is never ambiguous.
    $isAdmin = (bool) auth()->user()?->isAdmin();
    $emptyFloors = $isAdmin ? collect($design->floorNames())->map(fn ($name, $i) => ['floor' => $i + 1, 'name' => $name])->reject(fn ($f) => in_array($f['floor'], array_column($tour, 'floor'), true))->values() : collect();
    $misshapen = $isAdmin ? $design->misshapenPanoramas() : collect();
@endphp
@if(count($tour) || ($isAdmin && $design->images->contains(fn ($i) => $i->isPanorama())))
<section id="tour" class="container-page mt-16 scroll-mt-24">
    @php $sceneCount = collect($tour)->sum(fn ($f) => count($f['scenes'])); @endphp
    <x-section-header :eyebrow="trans_choice('ui.design.rooms_360', $sceneCount, ['count' => $sceneCount])" :title="__('ui.design.tour')" :subtitle="__('ui.design.tour_sub')" />

    <div class="space-y-8">
        @if($misshapen->isNotEmpty())
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-card border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm sm:px-6">
                <p class="max-w-3xl text-amber-700 dark:text-amber-300">
                    <strong>{{ $misshapen->count() }} image(s) marked 360° are not shown in the tour</strong> because they are not 2:1 panoramas
                    ({{ $misshapen->map(fn ($i) => ($i->title ?: 'untitled').' '.$i->width.'×'.$i->height)->implode(', ') }}).
                    They appear in the gallery as normal photos. Only admins see this note.
                </p>
                <a href="{{ route('admin.designs.edit', $design) }}" class="btn-ghost !px-4 !py-2 text-xs"><x-icon name="pencil" size="13" /> Fix in admin</a>
            </div>
        @endif
        @foreach($tour as $floor)
            <article class="card-static overflow-hidden" @if($unlocked) x-data="panoTour(@js($floor['scenes']))" @endif>
                <header class="flex flex-wrap items-center justify-between gap-3 p-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl2 bg-surface-2 text-center leading-none"><span><span class="block text-[9px] uppercase tracking-wider text-ink-faint">{{ __('ui.design.floor') }}</span><span class="block text-base font-semibold">{{ $floor['floor'] }}</span></span></span>
                        <div>
                            <h3 class="text-xl">{{ $floor['name'] }}</h3>
                            <p class="text-xs text-ink-faint">{{ trans_choice('ui.design.rooms_360', count($floor['scenes']), ['count' => count($floor['scenes'])]) }}</p>
                        </div>
                    </div>
                    @if($unlocked)
                        <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ $floor['name'] }}">
                            <span class="text-[11px] font-medium uppercase tracking-wider text-ink-faint">{{ __('ui.design.rooms') }}</span>
                            @foreach($floor['scenes'] as $i => $scene)
                                <button type="button" role="tab" @click="started ? select({{ $i }}) : start({{ $i }})" :aria-selected="active === {{ $i }}" class="chip" :class="active === {{ $i }} ? 'chip-active' : ''">{{ $scene['title'] }}</button>
                            @endforeach
                        </div>
                    @endif
                </header>

                @if($unlocked)
                    <div x-ref="stage" class="relative aspect-[16/9] bg-black sm:aspect-[21/9]">
                        <canvas x-ref="canvas" tabindex="0" class="h-full w-full cursor-grab touch-none outline-none" aria-label="{{ __('ui.design.drag_hint') }}"></canvas>

                        <div x-show="!started" class="absolute inset-0">
                            <img src="{{ thumb($floor['scenes'][0]['url'], 1600) }}" alt="" class="h-full w-full object-cover opacity-70" loading="lazy">
                            <button type="button" @click="start(0)" class="absolute inset-0 grid place-items-center text-white">
                                <span class="flex items-center gap-2 rounded-pill bg-black/60 px-5 py-3 text-sm font-medium backdrop-blur"><x-icon name="view360" size="18" /> {{ __('ui.design.tour') }}</span>
                            </button>
                        </div>
                        <div x-show="loading" x-transition.opacity class="pointer-events-none absolute inset-0 grid place-items-center">
                            <span class="rounded-pill bg-black/60 px-4 py-2 text-xs text-white backdrop-blur">{{ __('ui.design.loading') }}</span>
                        </div>
                        <div x-cloak x-show="failed" class="absolute inset-0">
                            <img src="{{ $floor['scenes'][0]['url'] }}" alt="" class="h-full w-full object-cover">
                            <p class="absolute inset-x-4 bottom-4 rounded-lg bg-black/70 px-3 py-2 text-xs text-white">{{ __('ui.design.webgl_missing') }}</p>
                        </div>

                        <p x-show="started && !failed" class="pointer-events-none absolute bottom-3 left-3 rounded-pill bg-black/50 px-3 py-1.5 text-[11px] text-white backdrop-blur">{{ __('ui.design.drag_hint') }}</p>
                        <div x-show="started && !failed" class="absolute bottom-3 right-3 flex gap-1.5">
                            <button type="button" @click="toggleAuto()" class="grid size-9 place-items-center rounded-full bg-black/55 text-white backdrop-blur transition hover:bg-black/75" title="{{ __('ui.design.autorotate') }}" aria-label="{{ __('ui.design.autorotate') }}" :aria-pressed="auto">
                                <span x-show="auto"><x-icon name="pause" size="15" /></span><span x-show="!auto"><x-icon name="play" size="15" /></span>
                            </button>
                            <button type="button" @click="reset()" class="grid size-9 place-items-center rounded-full bg-black/55 text-white backdrop-blur transition hover:bg-black/75" title="{{ __('ui.design.reset_view') }}" aria-label="{{ __('ui.design.reset_view') }}"><x-icon name="compass" size="15" /></button>
                            <button type="button" @click="fullscreen()" class="grid size-9 place-items-center rounded-full bg-black/55 text-white backdrop-blur transition hover:bg-black/75" title="{{ __('ui.design.fullscreen') }}" aria-label="{{ __('ui.design.fullscreen') }}"><x-icon name="maximize" size="15" /></button>
                        </div>
                    </div>
                    <template x-if="scenes[active]?.description">
                        <p class="px-4 py-3 text-sm text-ink-muted sm:px-6" x-text="scenes[active].description"></p>
                    </template>
                @else
                    {{-- Locked: no panorama URLs are rendered. --}}
                    <div class="relative aspect-[16/9] overflow-hidden bg-black sm:aspect-[21/9]">
                        <img src="{{ thumb($cover, 800) }}" alt="" class="h-full w-full scale-110 object-cover opacity-70 blur-xl">
                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-white">
                            <span class="grid size-14 place-items-center rounded-full bg-white/15 backdrop-blur"><x-icon name="view360" size="24" /></span>
                            <p class="max-w-md text-sm">{{ __('ui.design.tour_locked') }}</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                @foreach($floor['scenes'] as $scene)<span class="rounded-pill bg-white/15 px-3 py-1 text-xs backdrop-blur">{{ $scene['title'] }}</span>@endforeach
                            </div>
                            @if($buyForm)
                                <form method="POST" action="{{ $buyForm }}">@csrf<button class="btn-primary"><x-icon name="lock" size="15" /> {{ __('ui.design.unlock') }} · {{ price($design->price) }}</button></form>
                            @else
                                <a href="{{ $loginUrl }}" class="btn-primary"><x-icon name="lock" size="15" /> {{ __('ui.design.signin') }}</a>
                            @endif
                        </div>
                    </div>
                @endif
            </article>
        @endforeach

        @foreach($emptyFloors as $floor)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-card border border-dashed border-line px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl2 bg-surface-2 text-center leading-none"><span><span class="block text-[9px] uppercase tracking-wider text-ink-faint">{{ __('ui.design.floor') }}</span><span class="block text-base font-semibold">{{ $floor['floor'] }}</span></span></span>
                    <div>
                        <p class="font-medium">{{ $floor['name'] }}</p>
                        <p class="text-xs text-ink-faint">No 360° panoramas on this floor yet. Only admins see this row.</p>
                    </div>
                </div>
                <a href="{{ route('admin.designs.edit', $design) }}" class="btn-ghost !px-4 !py-2 text-xs"><x-icon name="pencil" size="13" /> Add panoramas</a>
            </div>
        @endforeach
    </div>
</section>
@endif
