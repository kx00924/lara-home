@props(['name', 'checked' => false, 'label' => null, 'submit' => false, 'confirm' => null])
{{-- `submit` sends the surrounding form on change; `confirm` names the item so a confirm dialog is shown first. --}}
<label class="inline-flex cursor-pointer items-center gap-2 text-sm">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked)
        @if($submit && $confirm) onchange="confirmToggle(this, {{ Js::from($confirm) }})" @elseif($submit) onchange="this.form.requestSubmit()" @endif>
    {{-- The knob follows the checkbox state through CSS, so a cancelled confirm snaps it back. --}}
    <span class="relative inline-block h-6 w-11 rounded-full bg-line transition peer-checked:bg-accent peer-checked:[&>span]:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40">
        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all"></span>
    </span>
    @if($label)<span class="text-ink-muted">{{ $label }}</span>@endif
</label>
