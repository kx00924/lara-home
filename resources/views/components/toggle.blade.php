@props(['name', 'checked' => false, 'label' => null, 'submit' => false, 'confirm' => null])
{{-- `submit` sends the surrounding form on change; `confirm` names the item so a confirm dialog is shown first. --}}
<label class="inline-flex cursor-pointer items-center gap-2 text-sm">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked)
        @if($submit && $confirm) onchange="confirmToggle(this, {{ Js::from($confirm) }})" @elseif($submit) onchange="this.form.requestSubmit()" @endif>
    {{-- The knob follows the checkbox state through CSS, so a cancelled confirm snaps it back.
         "On" is always green: the accent colour can be white, which would hide the white knob. --}}
    <span class="relative inline-block h-6 w-11 rounded-full bg-ink/20 transition peer-checked:bg-emerald-500 peer-checked:[&>span]:translate-x-5 peer-checked:[&>span>svg]:opacity-100 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40">
        <span class="absolute left-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-white text-emerald-600 shadow transition-all">
            <svg class="h-3 w-3 opacity-0 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
        </span>
    </span>
    @if($label)<span class="text-ink-muted">{{ $label }}</span>@endif
</label>
