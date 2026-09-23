@props(['eyebrow' => null, 'title', 'subtitle' => null, 'action' => null, 'href' => null, 'center' => false])
<div class="mb-12 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between {{ $center ? 'text-center sm:flex-col sm:items-center' : '' }}">
    <div class="max-w-[660px]">
        @if($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
        <h2 class="mt-3.5 text-[clamp(1.9rem,4vw,2.7rem)]">{{ $title }}</h2>
        @if($subtitle)<p class="mt-3.5 text-[1.02rem] text-muted">{{ $subtitle }}</p>@endif
    </div>
    @if($action && $href)<a href="{{ $href }}" class="btn-ghost shrink-0">{{ $action }} <x-icon name="arrow-right" size="15" /></a>@endif
</div>
