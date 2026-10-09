{{-- Notifications. Colours per type come from Site settings (window.SITE_THEME.toastColors). --}}
<div x-data="toasts()" x-init="
        @if(session('success')) push(@js(session('success')), 'success'); @endif
        @if(session('error')) push(@js(session('error')), 'error'); @endif
        @if(session('warning')) push(@js(session('warning')), 'warning'); @endif
        @if(session('info')) push(@js(session('info')), 'info'); @endif
        @if($errors->any() && !request()->routeIs('login', 'register')) push(@js($errors->first()), 'error'); @endif
     " @toast.window="push($event.detail.message, $event.detail.type)"
     class="pointer-events-none fixed bottom-5 right-5 z-[100] flex w-[min(92vw,380px)] flex-col gap-2">
    <template x-for="t in items" :key="t.id">
        <div class="pointer-events-auto flex items-start gap-3 rounded-card border border-l-4 bg-surface px-4 py-3 text-sm text-ink shadow-lift animate-rise"
             :style="`border-color: ${color(t.type)}66; border-left-color: ${color(t.type)}; background: color-mix(in srgb, ${color(t.type)} 10%, rgb(var(--c-surface)))`" role="status">
            <span class="mt-0.5 shrink-0" :style="`color: ${color(t.type)}`">
                <span x-show="t.type === 'success'"><x-icon name="check-circle" size="18" /></span>
                <span x-show="t.type === 'error'"><x-icon name="alert" size="18" /></span>
                <span x-show="t.type === 'warning'"><x-icon name="alert-triangle" size="18" /></span>
                <span x-show="t.type === 'info'"><x-icon name="info" size="18" /></span>
            </span>
            <span class="flex-1" x-text="t.message"></span>
            <button @click="remove(t.id)" class="ml-1 shrink-0 text-ink-faint hover:text-ink" aria-label="Dismiss"><x-icon name="x" size="14" /></button>
        </div>
    </template>
</div>
