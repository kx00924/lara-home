<div x-data="toasts()" x-init="
        @if(session('success')) push(@js(session('success')), 'success'); @endif
        @if(session('error')) push(@js(session('error')), 'error'); @endif
        @if(session('info')) push(@js(session('info')), 'info'); @endif
        @if($errors->any() && !request()->routeIs('login', 'register')) push(@js($errors->first()), 'error'); @endif
     " @toast.window="push($event.detail.message, $event.detail.type)"
     class="pointer-events-none fixed bottom-5 right-5 z-[110] flex flex-col gap-2">
    <template x-for="t in items" :key="t.id">
        <div class="pointer-events-auto flex items-center gap-3 rounded-card border border-brand/40 bg-panel px-4 py-3 text-sm shadow-panel animate-rise">
            <span :class="t.type === 'error' ? 'text-red-300' : t.type === 'success' ? 'text-emerald-300' : 'text-brand-bright'">
                <span x-show="t.type === 'success'"><x-icon name="check-circle" size="18" /></span>
                <span x-show="t.type === 'error'"><x-icon name="alert" size="18" /></span>
                <span x-show="t.type === 'info'"><x-icon name="info" size="18" /></span>
            </span>
            <span x-text="t.message"></span>
            <button @click="remove(t.id)" class="ml-2 text-faint hover:text-fg" aria-label="Dismiss"><x-icon name="x" size="14" /></button>
        </div>
    </template>
</div>
