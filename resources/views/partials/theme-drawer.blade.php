<div x-data="{ open: false }" @open-theme.window="open = true" @keydown.escape.window="open = false">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm" @click.self="open = false">
        <aside x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               class="absolute right-0 top-0 h-full w-full max-w-sm overflow-y-auto border-l border-line bg-surface p-6 text-ink shadow-lift">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-xl">{{ __('ui.theme.title') }}</h3>
                <button @click="open = false" class="rounded-full p-2 hover:bg-surface-2" aria-label="Close"><x-icon name="x" size="18" /></button>
            </div>
            <x-theme-panel compact />
        </aside>
    </div>
</div>
