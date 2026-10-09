{{-- Promise-based confirm dialog: window.confirmDialog({ title, message, okLabel, danger }) --}}
<div x-data x-cloak x-show="$store.confirm.open" x-transition.opacity.duration.150ms
     class="fixed inset-0 z-[115] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
     @click.self="$store.confirm.close(false)" @keydown.escape.window="$store.confirm.open && $store.confirm.close(false)" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-xl3 bg-surface p-6 text-ink shadow-lift">
        <h3 class="text-lg font-semibold" x-text="$store.confirm.title"></h3>
        <p class="mt-2 text-sm text-ink-muted" x-show="$store.confirm.message" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="$store.confirm.close(false)" class="btn-ghost">Cancel</button>
            <button type="button" @click="$store.confirm.close(true)" class="btn-primary" :class="$store.confirm.danger ? '!bg-red-600 !text-white' : ''" x-text="$store.confirm.okLabel"></button>
        </div>
    </div>
</div>
