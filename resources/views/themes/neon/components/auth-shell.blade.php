@props(['title', 'subtitle'])
<div class="shell grid min-h-screen items-center gap-10 pb-16 pt-32 lg:grid-cols-[0.85fr_1.15fr]">
    <div class="animate-rise">
        <span class="eyebrow">{{ $site['siteName'] }}</span>
        <h1 class="mt-4 text-[clamp(2rem,5vw,3rem)]">{{ $title }}</h1>
        <p class="mt-4 max-w-[480px] text-[1.02rem] text-muted">{{ $subtitle }}</p>
        <div class="group relative mt-10 hidden max-w-[360px] lg:block">
            <div aria-hidden="true" class="pointer-events-none absolute -inset-6 -z-10 rounded-panel bg-[radial-gradient(closest-side,rgb(34_211_238/0.25),transparent_75%)] blur-[38px]"></div>
            <div aria-hidden="true" class="absolute inset-0 -translate-x-3 translate-y-3 rounded-panel border border-brand/40 transition-transform duration-500 group-hover:-translate-x-1.5 group-hover:translate-y-1.5"></div>
            <div class="relative overflow-hidden rounded-panel border border-brand/60 bg-panel shadow-panel">
                <img src="{{ thumb($navCategories->first()?->image ?? $site['heroImage'], 900) }}" alt="" class="aspect-4/5 w-full object-cover transition-transform duration-700 group-hover:scale-[1.03]">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-linear-to-t from-canvas/70 via-transparent to-brand/[0.08]"></div>
                <span aria-hidden="true" class="pointer-events-none absolute left-4 top-4 size-6 border-l-2 border-t-2 border-brand/70"></span>
                <span aria-hidden="true" class="pointer-events-none absolute bottom-4 right-4 size-6 border-b-2 border-r-2 border-brand/70"></span>
            </div>
        </div>
    </div>
    <div class="card-static px-[22px] py-7 animate-rise sm:px-[34px] sm:py-9" style="animation-delay:100ms">{{ $slot }}</div>
</div>
