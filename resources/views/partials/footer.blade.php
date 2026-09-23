<footer class="mt-24 border-t border-line bg-surface">
    <div class="container-page grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl2 bg-accent font-serif text-lg font-semibold text-accent-fg">{{ mb_substr($site['siteName'], 0, 1) }}</span>
                <span class="font-serif text-xl">{{ $site['siteName'] }}</span>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-muted">{{ $site['tagline'] }}</p>
            <div class="mt-5 flex gap-2">
                @if($site['instagram'])<a href="{{ $site['instagram'] }}" target="_blank" rel="noreferrer" class="chip !p-2.5" aria-label="Instagram"><x-icon name="instagram" /></a>@endif
                @if($site['pinterest'])<a href="{{ $site['pinterest'] }}" target="_blank" rel="noreferrer" class="chip !p-2.5" aria-label="Pinterest"><x-icon name="pin" /></a>@endif
                @if($site['youtube'])<a href="{{ $site['youtube'] }}" target="_blank" rel="noreferrer" class="chip !p-2.5" aria-label="YouTube"><x-icon name="youtube" /></a>@endif
                @if($site['contactEmail'])<a href="mailto:{{ $site['contactEmail'] }}" class="chip !p-2.5" aria-label="Email"><x-icon name="mail" /></a>@endif
            </div>
        </div>
        <div>
            <p class="label">{{ __('ui.nav.styles') }}</p>
            <ul class="space-y-2 text-sm text-ink-muted">
                @foreach($navCategories->take(6) as $c)<li><a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="hover:text-ink">{{ $c->name }}</a></li>@endforeach
            </ul>
        </div>
        <div>
            <p class="label">{{ __('ui.nav.rooms') }}</p>
            <ul class="space-y-2 text-sm text-ink-muted">
                @foreach($navRooms->take(6) as $r)<li><a href="{{ route('designs.index', ['roomType' => $r->slug]) }}" class="hover:text-ink">{{ $r->name }}</a></li>@endforeach
            </ul>
        </div>
        <div>
            <p class="label">{{ __('ui.footer.company') }}</p>
            <ul class="space-y-2 text-sm text-ink-muted">
                <li><a href="{{ route('designs.index') }}" class="hover:text-ink">{{ __('ui.footer.explore') }}</a></li>
                <li><a href="{{ route('designs.index', ['price' => 'free']) }}" class="hover:text-ink">{{ __('ui.filters.free') }}</a></li>
                <li><a href="{{ route('dashboard') }}" class="hover:text-ink">{{ __('ui.nav.dashboard') }}</a></li>
                <li><a href="mailto:{{ $site['contactEmail'] }}" class="hover:text-ink">{{ __('ui.footer.contact') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-page flex flex-col items-center justify-between gap-2 py-5 text-xs text-ink-faint sm:flex-row">
            <span>{{ $site['footerText'] }}</span>
            <span>Photos courtesy of Unsplash · {{ config('services.stripe.secret') ? 'Payments by Stripe' : 'Demo checkout, no real payments' }}.</span>
        </div>
    </div>
</footer>
