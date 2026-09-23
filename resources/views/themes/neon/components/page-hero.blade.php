@props(['eyebrow', 'title', 'subtitle' => null])
<header class="page-hero">
    <div class="shell">
        <span class="eyebrow animate-rise">{{ $eyebrow }}</span>
        <h1 class="mt-4 text-[clamp(2.2rem,6vw,3.6rem)] animate-rise" style="animation-delay:80ms">{{ $title }}</h1>
        @if($subtitle)<p class="mt-4 max-w-[660px] text-[clamp(1rem,2vw,1.12rem)] text-muted animate-rise" style="animation-delay:160ms">{{ $subtitle }}</p>@endif
        @if(trim($slot))<div class="mt-6 animate-rise" style="animation-delay:220ms">{{ $slot }}</div>@endif
    </div>
</header>
