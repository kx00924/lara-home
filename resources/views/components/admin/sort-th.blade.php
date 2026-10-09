@props(['key', 'label', 'sort', 'dir'])
{{-- Sortable table header: click toggles asc/desc for this column, keeping search, filters and page size. --}}
@php
    $active = $sort === $key;
    $next = $active && $dir === 'asc' ? 'desc' : 'asc';
    $url = url()->current().'?'.\Illuminate\Support\Arr::query(array_filter(array_merge(request()->query(), ['sort' => $key, 'dir' => $next, 'page' => null]), fn ($v) => $v !== null && $v !== ''));
@endphp
<th {{ $attributes }}>
    <a href="{{ $url }}" class="inline-flex items-center gap-1 whitespace-nowrap hover:text-ink {{ $active ? 'text-ink' : '' }}" aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
        {{ $label }}
        @if($active)<x-icon :name="$dir === 'asc' ? 'chevron-up' : 'chevron-down'" size="13" />@else<x-icon name="sort" size="12" class="opacity-40" />@endif
    </a>
</th>
