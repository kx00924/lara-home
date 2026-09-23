@php $isHome = request()->routeIs('home'); @endphp
<header x-data="{ open: false, menu: null, scrolled: window.scrollY > 12, search: false }"
        @scroll.window.passive="scrolled = window.scrollY > 12"
        @keydown.escape.window="search = false; menu = null"
        :class="{ 'bg-transparent text-white': {{ $isHome ? '(!scrolled && !open)' : 'false' }}, 'border-b border-line bg-canvas/85 backdrop-blur-xl text-ink': {{ $isHome ? '(scrolled || open)' : 'true' }} }"
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300 {{ $isHome ? 'bg-transparent text-white' : 'border-b border-line bg-canvas/85 backdrop-blur-xl text-ink' }}">
    @if($site['announcement'])
        <div x-show="{{ $isHome ? 'scrolled' : 'true' }}" class="bg-accent px-4 py-1.5 text-center text-xs font-medium text-accent-fg">{{ $site['announcement'] }}</div>
    @endif
    <div class="container-page flex h-16 items-center justify-between gap-4" @click.outside="menu = null">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl2 font-serif text-lg font-semibold bg-accent text-accent-fg">{{ mb_substr($site['siteName'], 0, 1) }}</span>
            <span class="font-serif text-xl font-medium tracking-tight">{{ $site['siteName'] }}</span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" :class="{ '[&_.nav-link]:text-white/85 [&_.nav-link:hover]:bg-white/10 [&_.nav-link:hover]:text-white': {{ $isHome ? '(!scrolled && !open)' : 'false' }} }">
            <a href="{{ route('designs.index') }}" class="nav-link {{ request()->routeIs('designs.index') && !request()->query() ? 'nav-link-active' : '' }}">{{ __('ui.nav.designs') }}</a>
            <div class="relative">
                <button @click="menu = menu === 'styles' ? null : 'styles'" class="nav-link inline-flex items-center gap-1">{{ __('ui.nav.styles') }} <x-icon name="chevron-down" size="14" /></button>
                <div x-cloak x-show="menu === 'styles'" x-transition.opacity class="absolute left-0 top-full mt-2 w-[520px] rounded-xl3 border border-line bg-surface p-3 text-ink shadow-lift">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($navCategories as $c)
                            <a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="flex items-center gap-3 rounded-xl2 p-2 hover:bg-surface-2">
                                <img src="{{ thumb($c->image, 200) }}" alt="" class="h-12 w-16 rounded-lg object-cover" loading="lazy">
                                <span><span class="block text-sm font-medium">{{ $c->name }}</span><span class="block text-xs text-ink-faint">{{ $c->designs_count }} designs</span></span>
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ route('designs.index') }}" class="mt-2 block rounded-xl2 px-3 py-2 text-center text-sm text-accent hover:bg-surface-2">{{ __('ui.home.viewall') }} →</a>
                </div>
            </div>
            <div class="relative">
                <button @click="menu = menu === 'rooms' ? null : 'rooms'" class="nav-link inline-flex items-center gap-1">{{ __('ui.nav.rooms') }} <x-icon name="chevron-down" size="14" /></button>
                <div x-cloak x-show="menu === 'rooms'" x-transition.opacity class="absolute left-0 top-full mt-2 w-[520px] rounded-xl3 border border-line bg-surface p-3 text-ink shadow-lift">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($navRooms as $r)
                            <a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="flex items-center gap-3 rounded-xl2 p-2 hover:bg-surface-2">
                                <img src="{{ thumb($r->image, 200) }}" alt="" class="h-12 w-16 rounded-lg object-cover" loading="lazy">
                                <span><span class="block text-sm font-medium">{{ $r->name }}</span><span class="block text-xs text-ink-faint">{{ $r->designs_count }} designs</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <a href="{{ route('designs.index', ['price' => 'free']) }}" class="nav-link">{{ __('ui.filters.free') }}</a>
        </nav>

        <div class="flex items-center gap-1">
            <button @click="search = true" class="rounded-full p-2.5 transition hover:bg-surface-2/60" aria-label="{{ __('ui.nav.search') }}"><x-icon name="search" size="18" /></button>
            <div class="relative hidden sm:block">
                <button @click="menu = menu === 'lang' ? null : 'lang'" class="inline-flex items-center gap-1 rounded-full p-2.5 text-xs font-semibold transition hover:bg-surface-2/60" aria-label="Language"><x-icon name="globe" size="18" /> {{ strtoupper(app()->getLocale()) }}</button>
                <div x-cloak x-show="menu === 'lang'" x-transition.opacity class="absolute right-0 top-full mt-2 w-40 rounded-xl3 border border-line bg-surface p-1.5 text-ink shadow-lift">
                    @foreach($locales as $code => $label)
                        <a href="{{ route('lang', $code) }}" class="block rounded-xl2 px-3 py-2 text-sm hover:bg-surface-2 {{ app()->getLocale() === $code ? 'font-semibold text-accent' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <template x-if="$store.theme.allowOverride">
                <div class="flex items-center gap-1">
                    <button @click="$store.theme.toggle()" class="rounded-full p-2.5 transition hover:bg-surface-2/60" aria-label="Toggle dark mode">
                        <span x-show="$store.theme.isDark"><x-icon name="sun" size="18" /></span>
                        <span x-show="!$store.theme.isDark"><x-icon name="moon" size="18" /></span>
                    </button>
                    <button @click="$dispatch('open-theme')" class="hidden rounded-full p-2.5 transition hover:bg-surface-2/60 sm:block" aria-label="{{ __('ui.nav.customize') }}" title="{{ __('ui.nav.customize') }}"><x-icon name="palette" size="18" /></button>
                </div>
            </template>

            @auth
                <div class="relative hidden lg:block">
                    <button @click="menu = menu === 'user' ? null : 'user'" class="ml-1 flex items-center gap-2 rounded-pill bg-surface-2/70 py-1.5 pl-1.5 pr-3 text-sm font-medium transition hover:bg-surface-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent text-xs font-bold text-accent-fg">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        {{ explode(' ', auth()->user()->name)[0] }}
                    </button>
                    <div x-cloak x-show="menu === 'user'" x-transition.opacity class="absolute right-0 top-full mt-2 w-52 rounded-xl3 border border-line bg-surface p-1.5 text-ink shadow-lift">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-xl2 px-3 py-2 text-sm hover:bg-surface-2"><x-icon name="layout-dashboard" size="15" /> {{ __('ui.nav.dashboard') }}</a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-xl2 px-3 py-2 text-sm hover:bg-surface-2"><x-icon name="shield" size="15" /> {{ __('ui.nav.admin') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-xl2 px-3 py-2 text-left text-sm hover:bg-surface-2"><x-icon name="logout" size="15" /> {{ __('ui.nav.logout') }}</button></form>
                    </div>
                </div>
            @else
                <div class="ml-1 hidden items-center gap-1 lg:flex">
                    <a href="{{ route('login') }}" class="nav-link">{{ __('ui.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary">{{ __('ui.nav.register') }}</a>
                </div>
            @endauth
            <button class="rounded-full p-2.5 lg:hidden" @click="open = !open" aria-label="Menu">
                <span x-show="!open"><x-icon name="menu" size="20" /></span>
                <span x-cloak x-show="open"><x-icon name="x" size="20" /></span>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-cloak x-show="open" x-transition.opacity class="border-t border-line bg-canvas px-4 py-4 text-ink lg:hidden">
        <nav class="flex flex-col gap-1">
            <a href="{{ route('designs.index') }}" class="nav-link">{{ __('ui.nav.designs') }}</a>
            <a href="{{ route('designs.index', ['price' => 'free']) }}" class="nav-link">{{ __('ui.filters.free') }}</a>
            <p class="label mt-3">{{ __('ui.nav.styles') }}</p>
            <div class="flex flex-wrap gap-2">@foreach($navCategories as $c)<a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="chip">{{ $c->name }}</a>@endforeach</div>
            <p class="label mt-3">{{ __('ui.nav.rooms') }}</p>
            <div class="flex flex-wrap gap-2">@foreach($navRooms as $r)<a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="chip">{{ $r->name }}</a>@endforeach</div>
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                @foreach($locales as $code => $label)<a href="{{ route('lang', $code) }}" class="chip {{ app()->getLocale() === $code ? 'chip-active' : '' }}">{{ $label }}</a>@endforeach
                <button x-show="$store.theme.allowOverride" @click="$dispatch('open-theme')" class="chip"><x-icon name="palette" size="13" /> {{ __('ui.nav.customize') }}</button>
            </div>
            <div class="mt-4 flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-ghost flex-1"><x-icon name="user" size="15" /> {{ __('ui.nav.dashboard') }}</a>
                    @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn-ghost flex-1"><x-icon name="shield" size="15" /> {{ __('ui.nav.admin') }}</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-ghost"><x-icon name="logout" size="15" /></button></form>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost flex-1">{{ __('ui.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="btn-primary flex-1">{{ __('ui.nav.register') }}</a>
                @endauth
            </div>
        </nav>
    </div>

    {{-- Search overlay --}}
    <div x-cloak x-show="search" x-transition.opacity class="fixed inset-0 z-[80] bg-black/50 p-4 pt-24 backdrop-blur-sm text-ink" @click.self="search = false">
        <div class="mx-auto max-w-2xl" x-show="search" x-transition>
            <x-search-box large :autofocus="true" />
            <p class="mt-3 text-center text-xs text-white/70">Esc</p>
        </div>
    </div>
</header>
