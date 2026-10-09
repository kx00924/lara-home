@props(['paginator'])
{{-- "Showing x–y of z" plus a page-size selector that keeps the current search, filters and sort. --}}
@php
    $urlFor = fn (int $n) => url()->current().'?'.\Illuminate\Support\Arr::query(array_filter(array_merge(request()->query(), ['per_page' => $n, 'page' => null]), fn ($v) => $v !== null && $v !== ''));
@endphp
<div class="flex items-center gap-3 text-xs text-ink-muted">
    <span>@if($paginator->total()){{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}@else 0 results @endif</span>
    <label class="flex items-center gap-1.5">Per page
        <select class="input !w-auto !py-1 !text-xs" onchange="window.Alpine?.store('loader').show(); location.href = this.value" aria-label="Rows per page">
            @foreach(\App\Support\AdminTable::PER_PAGE_OPTIONS as $n)
                <option value="{{ $urlFor($n) }}" @selected($paginator->perPage() === $n)>{{ $n }}</option>
            @endforeach
        </select>
    </label>
</div>
