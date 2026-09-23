@if ($paginator->hasPages())
    <nav class="mt-10 flex items-center justify-center gap-1" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn-ghost h-10 w-10 !p-0 opacity-40"><x-icon name="chevron-left" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-ghost h-10 w-10 !p-0" aria-label="Previous"><x-icon name="chevron-left" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-ink-faint">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="h-10 min-w-10 rounded-pill bg-ink px-3 text-center text-sm font-medium leading-10 text-canvas">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="h-10 min-w-10 rounded-pill px-3 text-center text-sm font-medium leading-10 transition hover:bg-surface-2">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-ghost h-10 w-10 !p-0" aria-label="Next"><x-icon name="chevron-right" /></a>
        @else
            <span class="btn-ghost h-10 w-10 !p-0 opacity-40"><x-icon name="chevron-right" /></span>
        @endif
    </nav>
@endif
