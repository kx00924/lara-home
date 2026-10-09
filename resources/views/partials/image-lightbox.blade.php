{{-- Full-size image viewer: any element can $dispatch('open-image', { url, title }) --}}
<div x-data="{ img: null }" @open-image.window="img = $event.detail" @keydown.escape.window="img = null"
     x-cloak x-show="img" x-transition.opacity.duration.150ms class="fixed inset-0 z-[95] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm" @click.self="img = null">
    <figure class="flex max-h-full max-w-6xl flex-col items-center">
        <img :src="img?.url" :alt="img?.title || ''" class="max-h-[82vh] max-w-full rounded-xl2 object-contain shadow-lift">
        <figcaption class="mt-3 text-center text-sm text-white/80" x-text="img?.title"></figcaption>
    </figure>
    <button type="button" @click="img = null" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Close"><x-icon name="x" size="20" /></button>
</div>
