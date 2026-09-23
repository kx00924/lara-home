@props(['label' => null, 'hint' => null, 'name' => null])
<label {{ $attributes->merge(['class' => 'block']) }}>
    @if($label)<span class="label">{{ $label }}</span>@endif
    {{ $slot }}
    @if($name)@error($name)<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror @endif
    @if($hint)<span class="mt-1 block text-xs text-ink-faint">{{ $hint }}</span>@endif
</label>
