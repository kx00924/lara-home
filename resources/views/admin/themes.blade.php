@extends('layouts.admin')
@section('title', 'Themes')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="text-ink-muted">A theme is a complete set of page designs for the customer site. Preview any of them yourself, then activate the one visitors should see. The admin panel keeps its own look.</p>
            <p class="mt-2 text-xs text-ink-faint">Add a new theme by creating a folder under <code class="rounded bg-surface-2 px-1.5 py-0.5">resources/views/themes/</code>; it only needs the views it changes.</p>
        </div>
        @if($previewing)
            <a href="{{ route('theme', 'site') }}" class="btn-ghost"><x-icon name="x" size="14" /> Stop previewing “{{ config('themes.'.$previewing.'.label', ucfirst($previewing)) }}”</a>
        @endif
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach($themes as $t)
            <article class="card overflow-hidden {{ $t['active'] ? 'ring-2 ring-accent' : '' }}">
                {{-- miniature of the landing page in the theme's palette --}}
                <div class="relative aspect-[16/9] overflow-hidden border-b border-line p-5" style="background:{{ $t['swatches'][0] }}; color:{{ $t['swatches'][2] }}; font-family:'{{ $t['font'] }}', sans-serif">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="flex items-center gap-1.5"><span class="inline-block size-4 rounded" style="background:{{ $t['swatches'][3] }}"></span>{{ $site['siteName'] }}</span>
                        <span class="flex gap-2 opacity-60"><span>Designs</span><span>Styles</span><span>Rooms</span></span>
                    </div>
                    <p class="mt-6 max-w-[60%] text-2xl font-semibold leading-tight" style="color:{{ $t['name'] === 'neon' ? $t['swatches'][3] : $t['swatches'][2] }}">{{ $site['heroTitle'] }}</p>
                    <p class="mt-2 max-w-[55%] text-[10px] opacity-60">{{ \Illuminate\Support\Str::limit($site['heroSubtitle'], 80) }}</p>
                    <div class="mt-4 flex gap-2"><span class="rounded-md px-3 py-1 text-[10px] font-semibold" style="background:{{ $t['swatches'][3] }}; color:{{ $t['swatches'][0] }}">{{ $site['heroCtaText'] }}</span><span class="rounded-md border px-3 py-1 text-[10px]" style="border-color:{{ $t['swatches'][3] }}55">{{ __('ui.hero.browse') }}</span></div>
                    <div class="absolute bottom-5 right-5 grid w-[34%] grid-cols-2 gap-2">
                        @foreach(range(1, 4) as $i)<div class="aspect-[4/3] rounded-md border" style="background:{{ $t['swatches'][1] }}; border-color:{{ $t['swatches'][3] }}40"></div>@endforeach
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl">{{ $t['label'] }} @if($t['active'])<span class="badge badge-free ml-2">Live</span>@elseif($previewing === $t['name'])<span class="badge badge-warn ml-2">Previewing</span>@endif</h2>
                            <p class="mt-1 text-sm text-ink-muted">{{ $t['description'] }}</p>
                        </div>
                        <div class="flex shrink-0 gap-1">@foreach($t['swatches'] as $c)<span class="size-5 rounded-full border border-line" style="background:{{ $c }}" title="{{ $c }}"></span>@endforeach</div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div><dt class="text-ink-faint">Font</dt><dd class="font-medium">{{ $t['font'] ?: '—' }}</dd></div>
                        <div><dt class="text-ink-faint">Mode</dt><dd class="font-medium">{{ $t['mode'] ?: '—' }}</dd></div>
                    </dl>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('home', ['theme' => $t['name']]) }}" target="_blank" rel="noopener" class="btn-ghost"><x-icon name="external" size="14" /> Preview site</a>
                        @if($t['active'])
                            <span class="btn-ghost cursor-default opacity-60"><x-icon name="check" size="14" /> Active</span>
                        @else
                            <form method="POST" action="{{ route('admin.themes.activate', $t['name']) }}">@csrf<button class="btn-primary"><x-icon name="check" size="14" /> Activate for visitors</button></form>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endsection
