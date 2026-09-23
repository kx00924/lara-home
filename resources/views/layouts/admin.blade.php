<!doctype html>
<html lang="{{ app()->getLocale() }}" class="{{ ($site['defaultTheme'] ?? 'light') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · {{ $site['siteName'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;500;600&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        window.SITE_THEME = {{ Js::from(collect($site)->only(['defaultTheme','accentColor','textColorLight','textColorDark','backgroundLight','backgroundDark','headingFont','bodyFont','borderRadius','allowUserThemeOverride'])) }};
        try { var t = JSON.parse(localStorage.getItem('home.theme') || '{}'); var mode = t.mode || window.SITE_THEME.defaultTheme || 'dark'; document.documentElement.classList.toggle('dark', mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
@php
    $nav = [
        ['admin.dashboard', 'layout-dashboard', 'Dashboard', 'admin.dashboard'],
        ['admin.designs.index', 'images', 'Designs', 'admin.designs.*'],
        ['admin.categories.index', 'layout-grid', 'Design styles', 'admin.categories.*'],
        ['admin.room-types.index', 'door', 'Room types', 'admin.room-types.*'],
        ['admin.orders.index', 'receipt', 'Orders', 'admin.orders.*'],
        ['admin.users.index', 'users', 'Customers', 'admin.users.*'],
        ['admin.themes.index', 'palette', 'Themes', 'admin.themes.*'],
        ['admin.settings.edit', 'settings', 'Site settings', 'admin.settings.*'],
    ];
    $current = collect($nav)->first(fn ($n) => request()->routeIs($n[3]));
@endphp
<div class="flex min-h-screen bg-canvas" x-data="{ open: false }">
    <aside class="hidden w-64 shrink-0 flex-col border-r border-line bg-surface p-5 lg:flex">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl2 bg-accent font-serif text-lg font-semibold text-accent-fg">{{ mb_substr($site['siteName'], 0, 1) }}</span>
            <span><span class="block font-serif text-lg leading-tight">{{ $site['siteName'] }}</span><span class="block text-[11px] uppercase tracking-wider text-ink-faint">Admin</span></span>
        </a>
        @include('admin.partials.nav')
        <div class="mt-auto space-y-2 border-t border-line pt-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-xl2 px-3 py-2 text-sm text-ink-muted hover:bg-surface-2"><x-icon name="external" size="15" /> View site</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-xl2 px-3 py-2 text-sm text-ink-muted hover:bg-surface-2"><x-icon name="logout" size="15" /> Sign out</button></form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-line bg-canvas/85 px-4 backdrop-blur-xl sm:px-8">
            <div class="flex items-center gap-3">
                <button class="rounded-full p-2 hover:bg-surface-2 lg:hidden" @click="open = !open"><span x-show="!open"><x-icon name="menu" size="20" /></span><span x-cloak x-show="open"><x-icon name="x" size="20" /></span></button>
                <h1 class="font-serif text-xl">@yield('title', $current[2] ?? 'Admin')</h1>
            </div>
            <div class="flex items-center gap-2">
                <button @click="$store.theme.toggle()" class="rounded-full p-2 hover:bg-surface-2" aria-label="Toggle dark mode"><span x-show="$store.theme.isDark"><x-icon name="sun" size="18" /></span><span x-show="!$store.theme.isDark"><x-icon name="moon" size="18" /></span></button>
                <span class="hidden items-center gap-2 rounded-pill bg-surface-2 py-1.5 pl-1.5 pr-3 text-sm sm:flex"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-xs font-bold text-accent-fg">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>{{ auth()->user()->name }}</span>
            </div>
        </header>
        <div x-cloak x-show="open" class="border-b border-line bg-surface p-4 lg:hidden">@include('admin.partials.nav')</div>
        <main class="flex-1 p-4 sm:p-8">@yield('content')</main>
    </div>
</div>
@include('partials.toasts')
@stack('scripts')
</body>
</html>
