@props(['eyebrow' => null, 'title', 'subtitle' => null, 'action' => null, 'href' => null, 'center' => false])
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between {{ $center ? 'text-center sm:flex-col sm:items-center' : '' }}">
    <div class="max-w-2xl">
        @if($eyebrow)<p class="eyebrow mb-2">{{ $eyebrow }}</p>@endif
        <h2 class="text-3xl font-medium sm:text-4xl">{{ $title }}</h2>
        @if($subtitle)<p class="mt-2 text-ink-muted">{{ $subtitle }}</p>@endif
    </div>
    @if($action && $href)
        <a href="{{ $href }}" class="btn-ghost shrink-0">{{ $action }} <x-icon name="chevron-right" /></a>
    @endif
</div>
