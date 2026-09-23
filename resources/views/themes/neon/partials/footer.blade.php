@php $initials = collect(explode(' ', $site['siteName']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''); @endphp
<footer class="mt-10 border-t border-brand/25 bg-linear-to-b from-transparent to-brand/[0.04] pt-14">
    <div class="shell flex flex-col items-start justify-between gap-8 pb-9 md:flex-row md:items-center">
        <div class="flex items-center gap-3.5">
            <span class="grid size-[46px] place-items-center rounded-xl border border-brand/60 bg-brand/[0.06] font-mono font-bold text-brand-bright">{{ $initials }}</span>
            <div><strong>{{ $site['siteName'] }}</strong><p class="text-[0.85rem] text-muted">{{ $site['tagline'] }}</p></div>
        </div>
        <nav class="flex flex-wrap gap-x-6 gap-y-2 text-[0.92rem] text-muted" aria-label="Footer">
            <a href="{{ route('designs.index') }}" class="transition-colors hover:text-brand">{{ __('ui.nav.designs') }}</a>
            @foreach($navCategories->take(4) as $c)<a href="{{ route('designs.index', ['category' => $c->slug]) }}" class="transition-colors hover:text-brand">{{ $c->name }}</a>@endforeach
            <a href="{{ route('designs.index', ['price' => 'free']) }}" class="transition-colors hover:text-brand">{{ __('ui.filters.free') }}</a>
            <a href="{{ route('dashboard') }}" class="transition-colors hover:text-brand">{{ __('ui.nav.dashboard') }}</a>
        </nav>
        <div class="flex gap-2.5">
            @foreach([['instagram', $site['instagram'], 'Instagram'], ['pin', $site['pinterest'], 'Pinterest'], ['youtube', $site['youtube'], 'YouTube'], ['mail', $site['contactEmail'] ? 'mailto:'.$site['contactEmail'] : '', 'Email']] as [$icon, $url, $label])
                @if($url)<a href="{{ $url }}" target="_blank" rel="noreferrer noopener" aria-label="{{ $label }}" title="{{ $label }}" class="grid size-[42px] place-items-center rounded-[10px] border border-brand/25 text-muted transition duration-200 hover:-translate-y-[3px] hover:border-brand/60 hover:bg-brand/[0.06] hover:text-brand-bright"><x-icon :name="$icon" size="17" /></a>@endif
            @endforeach
        </div>
    </div>
    <div class="shell flex flex-col justify-between gap-3 border-t border-brand/[0.12] pb-7 pt-5 text-[0.8rem] text-faint sm:flex-row">
        <span>{{ $site['footerText'] }}</span>
        <span class="font-mono">Photos: Unsplash · {{ config('services.stripe.secret') ? 'Payments by Stripe' : 'demo checkout' }} · theme: neon</span>
    </div>
</footer>
