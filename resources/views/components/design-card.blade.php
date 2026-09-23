@props(['design', 'size' => 'md', 'owned' => false])
@php $tall = $size === 'lg'; @endphp
<a href="{{ route('designs.show', $design) }}" class="group block animate-rise">
    <div class="relative overflow-hidden rounded-card bg-surface-2 {{ $tall ? 'aspect-[4/5]' : 'aspect-[4/3]' }}">
        <img src="{{ thumb($design->cover, $tall ? 1000 : 700) }}" alt="{{ $design->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
        <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/0 to-black/0 opacity-80 transition group-hover:opacity-95"></div>
        <div class="absolute left-3 top-3 flex gap-2">
            @if($design->is_free)
                <span class="badge badge-free">{{ __('ui.design.free') }}</span>
            @elseif($owned)
                <span class="badge badge-glass">{{ __('ui.design.owned') }}</span>
            @else
                <span class="badge badge-glass"><x-icon name="lock" size="11" /> {{ price($design->price) }}</span>
            @endif
            @if($design->featured)<span class="badge badge-accent">Pick</span>@endif
        </div>
        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between text-white">
            <div class="min-w-0">
                <p class="truncate text-[11px] font-medium uppercase tracking-wider text-white/75">{{ $design->roomType?->name }} · {{ $design->category?->name }}</p>
                <h3 class="truncate font-serif text-lg leading-tight">{{ $design->title }}</h3>
            </div>
            <div class="ml-3 flex shrink-0 items-center gap-2 text-[11px] text-white/80">
                <span class="inline-flex items-center gap-1"><x-icon name="images" size="12" /> {{ $design->images_count ?? $design->images->count() }}</span>
                <span class="inline-flex items-center gap-1"><x-icon name="eye" size="12" /> {{ compact_number($design->views) }}</span>
            </div>
        </div>
    </div>
    <div class="mt-3 flex items-start justify-between gap-3 px-0.5">
        <p class="line-clamp-2 text-sm text-ink-muted">{{ $design->summary }}</p>
        <span class="inline-flex shrink-0 items-center gap-1 text-xs text-ink-faint"><x-icon name="heart" size="12" /> {{ compact_number($design->likes) }}</span>
    </div>
</a>
