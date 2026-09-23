@extends('layouts.admin')
@section('title', 'Site settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6" x-data="{ tab: @js($tab), f: @js($s), fg(hex) { const n = parseInt((hex || '#000').replace('#', ''), 16); const l = (0.2126 * ((n >> 16) & 255) + 0.7152 * ((n >> 8) & 255) + 0.0722 * (n & 255)) / 255; return l > 0.6 ? '#141210' : '#ffffff'; } }">
    @csrf @method('PUT')
    <input type="hidden" name="tab" :value="tab">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-1 rounded-pill bg-surface-2 p-1">
            @foreach([['branding', 'Branding & landing'], ['theme', 'Theme defaults'], ['commerce', 'Commerce & contact']] as [$k, $l])
                <button type="button" @click="tab = '{{ $k }}'" class="rounded-pill px-4 py-1.5 text-sm font-medium" :class="tab === '{{ $k }}' ? 'bg-surface shadow-soft' : 'text-ink-muted'">{{ $l }}</button>
            @endforeach
        </div>
        <button class="btn-primary"><x-icon name="save" size="15" /> Save settings</button>
    </div>
    @if($errors->any())<div class="rounded-xl2 bg-red-500/10 p-4 text-sm text-red-600"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Every tab stays in the DOM so all fields submit together. --}}
    <div x-show="tab === 'branding'" class="grid gap-6 xl:grid-cols-2">
        <section class="card space-y-4 p-6">
            <h2 class="text-xl">Brand</h2>
            <x-field label="Active theme" name="activeTheme" hint="Which set of page designs visitors see. Preview any theme yourself by adding ?theme=name to a page URL (?theme=site returns to this setting).">
                <select name="activeTheme" x-model="f.activeTheme" class="input">
                    @foreach($themes as $theme)<option value="{{ $theme }}">{{ ucfirst($theme) }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Site name" name="siteName"><input name="siteName" x-model="f.siteName" class="input" required></x-field>
            <x-field label="Tagline" name="tagline"><input name="tagline" x-model="f.tagline" class="input"></x-field>
            <x-field label="Announcement bar" name="announcement" hint="Leave empty to hide."><input name="announcement" x-model="f.announcement" class="input"></x-field>
            <x-field label="Footer text" name="footerText"><input name="footerText" x-model="f.footerText" class="input"></x-field>
        </section>
        <section class="card space-y-4 p-6">
            <h2 class="text-xl">Landing hero</h2>
            <x-field label="Headline" name="heroTitle"><input name="heroTitle" x-model="f.heroTitle" class="input"></x-field>
            <x-field label="Sub-headline" name="heroSubtitle"><textarea name="heroSubtitle" x-model="f.heroSubtitle" class="input"></textarea></x-field>
            <x-field label="Button text" name="heroCtaText"><input name="heroCtaText" x-model="f.heroCtaText" class="input"></x-field>
            <x-field label="Hero image URL" name="heroImage"><input name="heroImage" x-model="f.heroImage" class="input"></x-field>
            <template x-if="f.heroImage"><img :src="f.heroImage" alt="" class="aspect-[3/1] w-full rounded-xl2 object-cover"></template>
            <div class="grid grid-cols-3 gap-3">
                <x-field label="Stat: designs"><input name="statDesigns" x-model="f.statDesigns" class="input"></x-field>
                <x-field label="Stat: designers"><input name="statDesigners" x-model="f.statDesigners" class="input"></x-field>
                <x-field label="Stat: customers"><input name="statCustomers" x-model="f.statCustomers" class="input"></x-field>
            </div>
        </section>
    </div>

    <div x-show="tab === 'theme'" x-cloak class="grid gap-6 xl:grid-cols-2">
        <section class="card space-y-5 p-6">
            <div><h2 class="text-xl">Site-wide defaults</h2><p class="text-sm text-ink-muted">These apply to every visitor. Visitors can still personalise their own view from the palette icon unless you turn that off.</p></div>
            <div>
                <p class="label">Default mode</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['light', 'dark', 'system'] as $m)
                        <label class="chip cursor-pointer justify-center capitalize" :class="f.defaultTheme === '{{ $m }}' ? 'chip-active' : ''"><input type="radio" name="defaultTheme" value="{{ $m }}" x-model="f.defaultTheme" class="sr-only">{{ $m }}</label>
                    @endforeach
                </div>
            </div>
            @foreach([['accentColor', 'Accent colour'], ['textColorLight', 'Text (light mode)'], ['textColorDark', 'Text (dark mode)'], ['backgroundLight', 'Background (light)'], ['backgroundDark', 'Background (dark)']] as [$k, $l])
                <label class="block {{ $k === 'accentColor' ? '' : 'inline-block w-[calc(50%-0.5rem)]' }}">
                    <span class="label">{{ $l }}</span>
                    <span class="flex items-center gap-2 rounded-xl2 border border-line bg-surface px-2 py-1.5">
                        <input type="color" x-model="f.{{ $k }}" class="h-7 w-9 cursor-pointer rounded border-0 bg-transparent p-0">
                        <input name="{{ $k }}" x-model="f.{{ $k }}" class="w-full bg-transparent font-mono text-xs uppercase outline-none">
                    </span>
                </label>
            @endforeach
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Heading font"><select name="headingFont" x-model="f.headingFont" class="input">@foreach($fonts as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach</select></x-field>
                <x-field label="Body font"><select name="bodyFont" x-model="f.bodyFont" class="input">@foreach($fonts as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach</select></x-field>
            </div>
            <div><p class="label">Corner radius · <span x-text="f.borderRadius + 'px'"></span></p><input type="range" name="borderRadius" min="0" max="32" step="2" x-model.number="f.borderRadius" class="w-full accent-accent"></div>
            <div class="flex items-center justify-between"><span class="text-sm">Allow visitors to customise their view</span><x-toggle name="allowUserThemeOverride" :checked="(bool) $s['allowUserThemeOverride']" /></div>
        </section>
        <section class="card p-6">
            <h2 class="mb-3 text-xl">Preview</h2>
            <div class="space-y-4">
                @foreach([false, true] as $dark)
                    <div class="overflow-hidden border border-line" :style="`background:${f.{{ $dark ? 'backgroundDark' : 'backgroundLight' }}}; color:${f.{{ $dark ? 'textColorDark' : 'textColorLight' }}}; border-radius:${f.borderRadius}px; font-family:'${f.bodyFont}'`">
                        <div class="p-5">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em]" :style="`color:${f.accentColor}`">{{ $dark ? 'Dark' : 'Light' }} mode</p>
                            <h3 class="mt-1 text-2xl" :style="`font-family:'${f.headingFont}'`" x-text="f.heroTitle"></h3>
                            <p class="mt-2 text-sm opacity-70" x-text="f.tagline"></p>
                            <div class="mt-4 flex gap-2"><span class="rounded-full px-4 py-2 text-xs font-medium" :style="`background:${f.accentColor}; color:${fg(f.accentColor)}`" x-text="f.heroCtaText"></span><span class="rounded-full border border-current/20 px-4 py-2 text-xs font-medium">Secondary</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div x-show="tab === 'commerce'" x-cloak class="grid gap-6 xl:grid-cols-2">
        <section class="card space-y-4 p-6">
            <h2 class="text-xl">Currency</h2>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Currency code" name="currency"><input name="currency" x-model="f.currency" maxlength="3" class="input uppercase"></x-field>
                <x-field label="Symbol" name="currencySymbol"><input name="currencySymbol" x-model="f.currencySymbol" class="input"></x-field>
            </div>
            <p class="text-xs text-ink-faint">Payments run in demo mode until STRIPE_SECRET is set in .env.</p>
        </section>
        <section class="card space-y-4 p-6">
            <h2 class="text-xl">Contact & social</h2>
            <x-field label="Contact email" name="contactEmail"><input name="contactEmail" x-model="f.contactEmail" class="input"></x-field>
            <x-field label="Instagram"><input name="instagram" x-model="f.instagram" class="input"></x-field>
            <x-field label="Pinterest"><input name="pinterest" x-model="f.pinterest" class="input"></x-field>
            <x-field label="YouTube"><input name="youtube" x-model="f.youtube" class="input"></x-field>
        </section>
    </div>
</form>
@endsection
