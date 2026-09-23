@props(['name', 'checked' => false, 'label' => null, 'submit' => false])
<label class="inline-flex cursor-pointer items-center gap-2 text-sm">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked) {{ $submit ? 'onchange="this.form.requestSubmit()"' : '' }}>
    <span class="relative inline-block h-6 w-11 rounded-full bg-line transition peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40">
        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all {{ $checked ? 'translate-x-5' : '' }}"></span>
    </span>
    @if($label)<span class="text-ink-muted">{{ $label }}</span>@endif
</label>
