<!doctype html>
<html lang="{{ app()->getLocale() }}" class="dark" data-theme="neon">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05080c">
    <meta name="description" content="{{ $site['tagline'] }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $site['siteName'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Noto+Sans+KR:wght@400;500;700&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        // The neon theme is a fixed dark palette; the shared Alpine theme store stays inert.
        window.SITE_THEME = { defaultTheme: 'dark', allowUserThemeOverride: false };
    </script>
    @vite(['resources/css/theme-neon.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    @include('partials.nav')

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.toasts')
    @stack('scripts')
</body>
</html>
