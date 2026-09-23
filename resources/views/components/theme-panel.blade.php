@props(['compact' => false])
<div class="space-y-6 {{ $compact ? 'text-sm' : '' }}" x-data>
    <template x-if="!$store.theme.allowOverride">
        <p class="text-sm text-ink-muted">The studio has locked the appearance for this site.</p>
    </template>
    <template x-if="$store.theme.allowOverride">
        <div class="space-y-6">
            <section>
                <p class="label">{{ __('ui.theme.mode') }}</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach([['light', 'sun', __('ui.theme.light')], ['dark', 'moon', __('ui.theme.dark')], ['system', 'monitor', __('ui.theme.system')]] as [$m, $icon, $label])
                        <button @click="$store.theme.set({ mode: '{{ $m }}' })" class="flex flex-col items-center gap-1.5 rounded-xl2 border px-3 py-3 text-xs font-medium transition"
                                :class="$store.theme.theme.mode === '{{ $m }}' ? 'border-accent bg-accent-soft text-accent' : 'border-line hover:bg-surface-2'">
                            <x-icon :name="$icon" size="18" /> {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section>
                <p class="label">{{ __('ui.theme.accent') }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="p in $store.theme.presets" :key="p.value">
                        <button :title="p.name" @click="$store.theme.set({ accent: p.value })" class="relative flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:scale-110"
                                :style="`background:${p.value}; box-shadow:${($store.theme.theme.accent || '').toLowerCase() === p.value ? '0 0 0 2px rgb(var(--c-surface)), 0 0 0 4px ' + p.value : 'none'}`">
                            <span x-show="($store.theme.theme.accent || '').toLowerCase() === p.value"><x-icon name="check" size="14" /></span>
                        </button>
                    </template>
                    <label class="relative h-8 w-8 cursor-pointer overflow-hidden rounded-full border border-line bg-[conic-gradient(red,yellow,lime,cyan,blue,magenta,red)]" title="Custom">
                        <input type="color" :value="$store.theme.theme.accent || '#b45f3c'" @input="$store.theme.set({ accent: $event.target.value })" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-4">
                <label class="block">
                    <span class="label">{{ __('ui.theme.text') }}</span>
                    <span class="flex items-center gap-2 rounded-xl2 border border-line bg-surface px-2 py-1.5">
                        <input type="color" :value="$store.theme.isDark ? $store.theme.theme.textDark : $store.theme.theme.textLight" @input="$store.theme.set($store.theme.isDark ? { textDark: $event.target.value } : { textLight: $event.target.value })" class="h-7 w-9 cursor-pointer rounded border-0 bg-transparent p-0">
                        <span class="font-mono text-xs uppercase" x-text="$store.theme.isDark ? $store.theme.theme.textDark : $store.theme.theme.textLight"></span>
                    </span>
                </label>
                <label class="block">
                    <span class="label">{{ __('ui.theme.bg') }}</span>
                    <span class="flex items-center gap-2 rounded-xl2 border border-line bg-surface px-2 py-1.5">
                        <input type="color" :value="$store.theme.isDark ? $store.theme.theme.bgDark : $store.theme.theme.bgLight" @input="$store.theme.set($store.theme.isDark ? { bgDark: $event.target.value } : { bgLight: $event.target.value })" class="h-7 w-9 cursor-pointer rounded border-0 bg-transparent p-0">
                        <span class="font-mono text-xs uppercase" x-text="$store.theme.isDark ? $store.theme.theme.bgDark : $store.theme.theme.bgLight"></span>
                    </span>
                </label>
            </section>

            <section>
                <p class="label">{{ __('ui.theme.font') }}</p>
                <select class="input" :value="$store.theme.theme.headingFont" @change="$store.theme.set({ headingFont: $event.target.value })">
                    <template x-for="f in $store.theme.fonts" :key="f"><option :value="f" x-text="f" :selected="f === $store.theme.theme.headingFont"></option></template>
                </select>
            </section>

            <section class="grid grid-cols-2 gap-4">
                <div>
                    <p class="label">{{ __('ui.theme.size') }} · <span x-text="Math.round($store.theme.theme.fontScale * 100) + '%'"></span></p>
                    <input type="range" min="0.85" max="1.25" step="0.05" :value="$store.theme.theme.fontScale" @input="$store.theme.set({ fontScale: Number($event.target.value) })" class="w-full accent-accent">
                </div>
                <div>
                    <p class="label">{{ __('ui.theme.radius') }} · <span x-text="$store.theme.theme.radius + 'px'"></span></p>
                    <input type="range" min="0" max="32" step="2" :value="$store.theme.theme.radius" @input="$store.theme.set({ radius: Number($event.target.value) })" class="w-full accent-accent">
                </div>
            </section>

            <button @click="$store.theme.reset()" :disabled="!$store.theme.hasOverride" class="btn-ghost w-full"><x-icon name="rotate" size="14" /> {{ __('ui.theme.reset') }}</button>
        </div>
    </template>
</div>
