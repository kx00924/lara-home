<!doctype html>
<html lang="{{ app()->getLocale() }}" class="{{ ($site['defaultTheme'] ?? 'light') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $site['tagline'] }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $site['siteName'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,400;0,500;0,600;1,400&family=Noto+Sans:wght@300;400;500;600;700&family=Noto+Sans+KR:wght@300;400;500;700&family=Noto+Sans+JP:wght@300;400;500;700&family=Noto+Serif+KR:wght@400;600&family=Noto+Serif+JP:wght@400;600&display=swap" rel="stylesheet">
    <script>
        window.SITE_THEME = {{ Js::from(collect($site)->only(['defaultTheme','accentColor','textColorLight','textColorDark','backgroundLight','backgroundDark','headingFont','bodyFont','borderRadius','allowUserThemeOverride'])) }};
        // Apply the stored mode before styles load to avoid a flash.
        try {
            var t = JSON.parse(localStorage.getItem('home.theme') || '{}');
            var mode = (window.SITE_THEME.allowUserThemeOverride !== false && t.mode) || window.SITE_THEME.defaultTheme || 'dark';
            var sys = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', mode === 'dark' || (mode === 'system' && sys));
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">
    @include('partials.nav')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.theme-drawer')
    @include('partials.toasts')
    @stack('scripts')
</body>
</html>
