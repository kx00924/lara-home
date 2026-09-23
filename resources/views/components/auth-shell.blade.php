@props(['title', 'subtitle'])
<div class="container-page grid min-h-screen items-stretch gap-8 pb-12 pt-24 lg:grid-cols-2">
    <div class="relative hidden overflow-hidden rounded-xl3 lg:block">
        <img src="{{ thumb($navCategories->first()?->image ?? $site['heroImage'], 1200) }}" alt="" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        <div class="absolute bottom-10 left-10 right-10 text-white">
            <p class="eyebrow !text-white/70">{{ $site['siteName'] }}</p>
            <p class="mt-2 font-serif text-3xl leading-snug">“A room is finished when there is nothing left to remove.”</p>
        </div>
    </div>
    <div class="flex items-center">
        <div class="mx-auto w-full max-w-md">
            <h1 class="text-4xl">{{ $title }}</h1>
            <p class="mt-2 text-ink-muted">{{ $subtitle }}</p>
            <div class="card mt-8 p-6 sm:p-8">{{ $slot }}</div>
        </div>
    </div>
</div>
