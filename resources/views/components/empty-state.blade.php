@props(['icon' => null, 'title', 'text' => null])
<div class="card flex flex-col items-center px-6 py-16 text-center">
    @if($icon)<div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent-soft text-accent"><x-icon :name="$icon" size="26" /></div>@endif
    <h3 class="text-xl">{{ $title }}</h3>
    @if($text)<p class="mt-2 max-w-md text-sm text-ink-muted">{{ $text }}</p>@endif
    @if(trim($slot))<div class="mt-6">{{ $slot }}</div>@endif
</div>
