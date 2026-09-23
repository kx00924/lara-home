@props(['large' => false, 'autofocus' => false])
<div x-data="searchBox(@js(route('designs.suggest')), @js(route('designs.index')))" @click.outside="open = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="flex items-center gap-2 rounded-pill border border-line bg-surface text-ink shadow-soft {{ $large ? 'px-5 py-3' : 'px-4 py-2' }}">
        <x-icon name="search" :size="$large ? 20 : 16" class="text-ink-faint" />
        <input x-model="q" @input="onInput" @focus="items.length && (open = true)" @keydown="onKey" type="search" placeholder="{{ __('ui.nav.search') }}"
               class="w-full bg-transparent outline-none placeholder:text-ink-faint {{ $large ? 'text-base' : 'text-sm' }}" aria-label="{{ __('ui.nav.search') }}" @if($autofocus) x-init="$nextTick(() => $el.focus())" @endif>
        @if($large)
            <button @click="go(null)" class="btn-primary !px-4 !py-2" aria-label="Search"><x-icon name="arrow-right" size="16" /></button>
        @endif
    </div>
    <ul x-cloak x-show="open && items.length" x-transition.opacity class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl3 border border-line bg-surface p-1.5 shadow-lift">
        <template x-for="(it, i) in items" :key="it.url">
            <li>
                <button @mouseenter="active = i" @click="go(it)" class="flex w-full items-center gap-3 rounded-xl2 px-3 py-2 text-left text-sm" :class="active === i ? 'bg-surface-2' : ''">
                    <template x-if="it.image"><img :src="it.image" alt="" class="h-9 w-12 rounded-md object-cover"></template>
                    <template x-if="!it.image"><span class="flex h-9 w-12 items-center justify-center rounded-md bg-accent-soft text-accent"><x-icon name="layout-grid" size="16" /></span></template>
                    <span class="flex-1 truncate" x-text="it.label"></span>
                    <span class="text-[11px] uppercase tracking-wider text-ink-faint" x-text="it.type"></span>
                </button>
            </li>
        </template>
        <li>
            <button @click="go(null)" class="flex w-full items-center gap-2 rounded-xl2 px-3 py-2 text-left text-sm text-accent hover:bg-surface-2"><x-icon name="search" size="14" /> <span x-text="'Search all for “' + q + '”'"></span></button>
        </li>
    </ul>
</div>
