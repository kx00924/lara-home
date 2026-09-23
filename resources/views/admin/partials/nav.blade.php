<nav class="flex flex-col gap-1">
    @foreach($nav as [$route, $icon, $label, $pattern])
        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl2 px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($pattern) ? 'bg-accent text-accent-fg' : 'text-ink-muted hover:bg-surface-2 hover:text-ink' }}"><x-icon :name="$icon" size="17" /> {{ $label }}</a>
    @endforeach
</nav>
