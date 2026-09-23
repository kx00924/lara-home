@if ($paginator->hasPages())
    <nav class="mt-10 flex items-center justify-center gap-1.5" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="grid size-10 place-items-center rounded-[10px] border border-brand/15 text-faint opacity-50"><x-icon name="chevron-left" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-[10px] border border-brand/25 text-muted transition hover:border-brand/60 hover:text-brand-bright" aria-label="Previous"><x-icon name="chevron-left" /></a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="px-2 font-mono text-faint">{{ $element }}</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="grid h-10 min-w-10 place-items-center rounded-[10px] bg-linear-to-br from-brand-bright to-brand px-3 font-mono text-sm font-semibold text-canvas shadow-glow">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="grid h-10 min-w-10 place-items-center rounded-[10px] border border-brand/25 px-3 font-mono text-sm text-muted transition hover:border-brand/60 hover:text-brand-bright">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-[10px] border border-brand/25 text-muted transition hover:border-brand/60 hover:text-brand-bright" aria-label="Next"><x-icon name="chevron-right" /></a>
        @else
            <span class="grid size-10 place-items-center rounded-[10px] border border-brand/15 text-faint opacity-50"><x-icon name="chevron-right" /></span>
        @endif
    </nav>
@endif
