@props(['design', 'size' => 'md', 'owned' => false])
<a href="{{ route('designs.show', $design) }}" class="card group flex flex-col overflow-hidden" aria-label="{{ $design->title }}">
    <div class="relative overflow-hidden border-b border-brand/25 {{ $size === 'lg' ? 'aspect-[4/3]' : 'h-[190px]' }}">
        <img src="{{ thumb($design->cover, $size === 'lg' ? 1000 : 700) }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
        <div class="absolute inset-0 bg-linear-to-t from-canvas/80 via-transparent to-brand/[0.06]"></div>
        <span class="absolute left-3 top-3">
            @if($design->is_free)<span class="badge badge-free">{{ __('ui.design.free') }}</span>
            @elseif($owned)<span class="badge badge-glass"><x-icon name="unlock" size="11" /> {{ __('ui.design.owned') }}</span>
            @else<span class="badge badge-glass"><x-icon name="lock" size="11" /> {{ price($design->price) }}</span>@endif
        </span>
        <span class="absolute right-3 top-3 grid size-[34px] -translate-y-1.5 place-items-center rounded-[9px] border border-brand/25 bg-canvas/60 text-brand-bright opacity-0 transition duration-200 group-hover:translate-y-0 group-hover:opacity-100"><x-icon name="arrow-right" size="15" class="-rotate-45" /></span>
    </div>
    <div class="flex flex-1 flex-col px-[22px] pb-5 pt-[18px]">
        <div class="mb-3 flex items-center justify-between gap-3">
            <span class="chip">{{ $design->category?->name }}</span>
            <span class="font-mono text-[0.74rem] text-faint">{{ $design->roomType?->name }}</span>
        </div>
        <h3 class="mb-2 text-[1.06rem]">{{ $design->title }}</h3>
        <p class="line-clamp-2 text-[0.9rem] text-muted">{{ $design->summary }}</p>
        <div class="mt-auto flex items-center justify-between gap-3 border-t border-brand/[0.12] pt-4">
            <div class="flex flex-wrap gap-2.5">
                @foreach(array_slice($design->tags ?? [], 0, 2) as $t)<span class="tag-dot">{{ $t }}</span>@endforeach
            </div>
            <span class="flex shrink-0 items-center gap-3 font-mono text-[0.72rem] text-faint">
                <span class="inline-flex items-center gap-1"><x-icon name="images" size="12" /> {{ $design->images_count ?? $design->images->count() }}</span>
                <span class="inline-flex items-center gap-1"><x-icon name="heart" size="12" /> {{ compact_number($design->likes) }}</span>
            </span>
        </div>
    </div>
</a>
