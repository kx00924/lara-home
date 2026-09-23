<div class="space-y-7">
    <div>
        <p class="label">{{ __('ui.filters.style') }}</p>
        <div class="space-y-1">
            @foreach($categories as $c)
                @php $on = in_array($c->slug, $filters['category']); @endphp
                <a href="{{ $toggle('category', $c->slug) }}" class="flex items-center justify-between rounded-xl2 px-2 py-1.5 text-sm hover:bg-surface-2">
                    <span class="flex items-center gap-2.5"><span class="flex h-4 w-4 items-center justify-center rounded border {{ $on ? 'border-accent bg-accent text-accent-fg' : 'border-line' }}">@if($on)<x-icon name="check" size="11" />@endif</span><span class="{{ $on ? 'font-medium' : 'text-ink-muted' }}">{{ $c->name }}</span></span>
                    <span class="text-xs text-ink-faint">{{ $c->designs_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
    <div>
        <p class="label">{{ __('ui.filters.room') }}</p>
        <div class="space-y-1">
            @foreach($rooms as $r)
                @php $on = in_array($r->slug, $filters['roomType']); @endphp
                <a href="{{ $toggle('roomType', $r->slug) }}" class="flex items-center justify-between rounded-xl2 px-2 py-1.5 text-sm hover:bg-surface-2">
                    <span class="flex items-center gap-2.5"><span class="flex h-4 w-4 items-center justify-center rounded border {{ $on ? 'border-accent bg-accent text-accent-fg' : 'border-line' }}">@if($on)<x-icon name="check" size="11" />@endif</span><span class="{{ $on ? 'font-medium' : 'text-ink-muted' }}">{{ $r->name }}</span></span>
                    <span class="text-xs text-ink-faint">{{ $r->designs_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
    <div>
        <p class="label">{{ __('ui.filters.price') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach([['', __('ui.filters.all')], ['free', __('ui.filters.free')], ['paid', __('ui.filters.paid')]] as [$v, $l])
                <a href="{{ $url(['price' => $v]) }}" class="chip {{ $filters['price'] === $v ? 'chip-active' : '' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
    @if(count($tags))
        <div>
            <p class="label">{{ __('ui.filters.tags') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($tags as $tag => $n)<a href="{{ $toggle('tag', $tag) }}" class="chip {{ in_array($tag, $filters['tag']) ? 'chip-active' : '' }}">#{{ $tag }}</a>@endforeach
            </div>
        </div>
    @endif
    @if($activeCount)
        <a href="{{ route('designs.index', array_filter(['q' => $filters['q']])) }}" class="btn-ghost w-full"><x-icon name="x" size="14" /> {{ __('ui.filters.clear') }}</a>
    @endif
</div>
