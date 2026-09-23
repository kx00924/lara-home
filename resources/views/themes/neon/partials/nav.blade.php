@php
    $initials = collect(explode(' ', $site['siteName']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp
<header x-data="{ open: false, menu: null, scrolled: window.scrollY > 16, search: false }"
        @scroll.window.passive="scrolled = window.scrollY > 16"
        @keydown.escape.window="search = false; menu = null; open = false"
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        class="fixed inset-x-0 top-0 z-50 flex h-18 items-center border-b transition duration-300"
        :class="scrolled || open ? 'border-brand/25 bg-canvas/80 backdrop-blur-lg' : 'border-transparent'">
    <div class="shell flex items-center gap-4" @click.outside="menu = null">
        <a href="{{ route('home') }}" class="group mr-auto flex items-center gap-3" aria-label="{{ $site['siteName'] }} home">
            <span class="grid size-10 place-items-center rounded-[10px] border border-brand/60 bg-brand/[0.06] font-mono text-sm font-bold tracking-wide text-brand-bright shadow-[inset_0_0_18px_rgb(34_211_238/0.14)] transition-shadow duration-300 group-hover:shadow-[inset_0_0_22px_rgb(34_211_238/0.14),0_0_22px_-6px_#22d3ee]">{{ $initials }}</span>
            <span class="flex flex-col text-[0.95rem] font-semibold leading-tight">
                {{ $site['siteName'] }}
                <em class="hidden text-[0.7rem] font-medium not-italic tracking-wide text-faint sm:block">{{ __('ui.nav.designs') }} · {{ __('ui.nav.styles') }} · {{ __('ui.nav.rooms') }}</em>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary">
            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">{{ __('ui.nav.home') }}</a>
            <a href="{{ route('designs.index') }}" class="nav-link {{ request()->routeIs('designs.index') && !request()->query() ? 'nav-link-active' : '' }}">{{ __('ui.nav.designs') }}</a>
            <div class="relative">
                <button @click="menu = menu === 'styles' ? null : 'styles'" class="nav-link inline-flex items-center gap-1">{{ __('ui.nav.styles') }} <x-icon name="chevron-down" size="14" /></button>
                <div x-cloak x-show="menu === 'styles'" x-transition.opacity class="card-static absolute left-0 top-full mt-3 w-[520px] p-3 shadow-panel">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($navCategories as $c)
                            <a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-brand/[0.06]">
                                <img src="{{ thumb($c->image, 200) }}" alt="" class="h-12 w-16 rounded-md border border-brand/20 object-cover" loading="lazy">
                                <span><span class="block text-sm font-medium">{{ $c->name }}</span><span class="block font-mono text-[0.7rem] text-faint">{{ $c->designs_count }} designs</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="relative">
                <button @click="menu = menu === 'rooms' ? null : 'rooms'" class="nav-link inline-flex items-center gap-1">{{ __('ui.nav.rooms') }} <x-icon name="chevron-down" size="14" /></button>
                <div x-cloak x-show="menu === 'rooms'" x-transition.opacity class="card-static absolute left-0 top-full mt-3 w-[520px] p-3 shadow-panel">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($navRooms as $r)
                            <a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-brand/[0.06]">
                                <img src="{{ thumb($r->image, 200) }}" alt="" class="h-12 w-16 rounded-md border border-brand/20 object-cover" loading="lazy">
                                <span><span class="block text-sm font-medium">{{ $r->name }}</span><span class="block font-mono text-[0.7rem] text-faint">{{ $r->designs_count }} designs</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <a href="{{ route('designs.index', ['price' => 'free']) }}" class="nav-link">{{ __('ui.filters.free') }}</a>
        </nav>

        <button @click="search = true" class="grid size-[42px] place-items-center rounded-[10px] border border-brand/25 bg-brand/[0.03] text-muted transition hover:border-brand/60 hover:text-brand-bright" aria-label="{{ __('ui.nav.search') }}"><x-icon name="search" size="17" /></button>
        <div class="relative hidden sm:block">
            <button @click="menu = menu === 'lang' ? null : 'lang'" class="grid h-[42px] place-items-center rounded-[10px] border border-brand/25 bg-brand/[0.03] px-3 font-mono text-xs font-semibold text-muted transition hover:border-brand/60 hover:text-brand-bright" aria-label="Language">{{ strtoupper(app()->getLocale()) }}</button>
            <div x-cloak x-show="menu === 'lang'" x-transition.opacity class="card-static absolute right-0 top-full mt-3 w-40 p-1.5 shadow-panel">
                @foreach($locales as $code => $label)
                    <a href="{{ route('lang', $code) }}" class="block rounded-lg px-3 py-2 text-sm transition hover:bg-brand/[0.06] {{ app()->getLocale() === $code ? 'text-brand-bright' : 'text-muted' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @auth
            <div class="relative hidden lg:block">
                <button @click="menu = menu === 'user' ? null : 'user'" class="flex h-[42px] items-center gap-2 rounded-[10px] border border-brand/25 bg-brand/[0.03] py-1 pl-1 pr-3 text-sm font-medium transition hover:border-brand/60">
                    <span class="grid size-8 place-items-center rounded-lg bg-linear-to-br from-brand-bright to-brand font-mono text-xs font-bold text-canvas">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    {{ explode(' ', auth()->user()->name)[0] }}
                </button>
                <div x-cloak x-show="menu === 'user'" x-transition.opacity class="card-static absolute right-0 top-full mt-3 w-52 p-1.5 shadow-panel">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted transition hover:bg-brand/[0.06] hover:text-fg"><x-icon name="layout-dashboard" size="15" /> {{ __('ui.nav.dashboard') }}</a>
                    @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted transition hover:bg-brand/[0.06] hover:text-fg"><x-icon name="shield" size="15" /> {{ __('ui.nav.admin') }}</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-muted transition hover:bg-brand/[0.06] hover:text-fg"><x-icon name="logout" size="15" /> {{ __('ui.nav.logout') }}</button></form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}" class="nav-link hidden lg:inline-flex">{{ __('ui.nav.login') }}</a>
            <a href="{{ route('register') }}" class="btn-primary hidden px-5 py-2.5 text-[0.85rem] lg:inline-flex">{{ __('ui.nav.register') }}</a>
        @endauth

        <button @click="open = !open" :aria-expanded="open" aria-label="Menu" class="flex size-[42px] flex-col justify-center gap-[5px] rounded-[10px] border border-brand/25 bg-brand/[0.06] px-2.5 lg:hidden">
            <span class="h-0.5 w-full rounded-sm bg-brand transition-transform duration-300" :class="open ? 'translate-y-[7px] rotate-45' : ''"></span>
            <span class="h-0.5 w-full rounded-sm bg-brand transition-opacity duration-200" :class="open ? 'opacity-0' : ''"></span>
            <span class="h-0.5 w-full rounded-sm bg-brand transition-transform duration-300" :class="open ? '-translate-y-[7px] -rotate-45' : ''"></span>
        </button>
    </div>

    {{-- mobile drawer --}}
    <div class="fixed inset-x-0 top-18 flex max-h-[calc(100vh-4.5rem)] flex-col gap-1.5 overflow-y-auto border-b border-brand/25 bg-canvas/[0.97] px-6 pb-7 pt-5 backdrop-blur-xl transition-all duration-300 lg:hidden" :class="open ? 'visible translate-y-0 opacity-100' : 'invisible -translate-y-[120%] opacity-0'">
        <a href="{{ route('home') }}" class="rounded-lg border border-transparent px-4 py-3.5 text-[1.02rem] font-medium text-muted">{{ __('ui.nav.home') }}</a>
        <a href="{{ route('designs.index') }}" class="rounded-lg border border-transparent px-4 py-3.5 text-[1.02rem] font-medium text-muted">{{ __('ui.nav.designs') }}</a>
        <a href="{{ route('designs.index', ['price' => 'free']) }}" class="rounded-lg border border-transparent px-4 py-3.5 text-[1.02rem] font-medium text-muted">{{ __('ui.filters.free') }}</a>
        <p class="label mt-3">{{ __('ui.nav.styles') }}</p>
        <div class="flex flex-wrap gap-2">@foreach($navCategories as $c)<a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="chip">{{ $c->name }}</a>@endforeach</div>
        <p class="label mt-3">{{ __('ui.nav.rooms') }}</p>
        <div class="flex flex-wrap gap-2">@foreach($navRooms as $r)<a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="chip">{{ $r->name }}</a>@endforeach</div>
        <div class="mt-3 flex flex-wrap gap-2">@foreach($locales as $code => $label)<a href="{{ route('lang', $code) }}" class="chip {{ app()->getLocale() === $code ? 'chip-active' : '' }}">{{ $label }}</a>@endforeach</div>
        <div class="mt-4 flex gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-ghost flex-1">{{ __('ui.nav.dashboard') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">@csrf<button class="btn-ghost w-full">{{ __('ui.nav.logout') }}</button></form>
            @else
                <a href="{{ route('login') }}" class="btn-ghost flex-1">{{ __('ui.nav.login') }}</a>
                <a href="{{ route('register') }}" class="btn-primary flex-1">{{ __('ui.nav.register') }}</a>
            @endauth
        </div>
    </div>

    {{-- search overlay --}}
    <div x-cloak x-show="search" x-transition.opacity class="fixed inset-0 z-[80] bg-[#020508]/85 p-4 pt-24 backdrop-blur-sm" @click.self="search = false">
        <div class="mx-auto max-w-2xl" x-show="search" x-transition>
            <x-search-box large :autofocus="true" />
            <p class="mt-3 text-center font-mono text-xs text-faint">esc</p>
        </div>
    </div>
</header>
