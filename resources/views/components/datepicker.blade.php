@props(['name', 'value' => '', 'max' => '', 'min' => '', 'placeholder' => 'Pick a date', 'disabledExpr' => 'false'])
{{-- Popover calendar (Alpine "datepicker"); the value is submitted through a hidden input as YYYY-MM-DD. --}}
<div x-data="datepicker(@js($value), @js($max), @js($min))" @click.outside="open = false" @keydown.escape="open = false" class="relative" {{ $attributes }}>
    <input type="hidden" name="{{ $name }}" :value="value" :disabled="{{ $disabledExpr }}">
    <button type="button" @click="toggle()" class="input flex !w-auto min-w-[150px] items-center gap-2 !py-1.5 !text-xs" :class="open ? 'border-accent ring-2 ring-accent/25' : ''" aria-haspopup="dialog" :aria-expanded="open">
        <x-icon name="calendar" size="14" class="text-ink-faint" />
        <span x-text="label || @js($placeholder)" :class="label ? '' : 'text-ink-faint'"></span>
    </button>
    <div x-cloak x-show="open" x-transition.origin.top.left class="absolute left-0 top-full z-40 mt-2 w-72 rounded-xl3 border border-line bg-surface p-3 shadow-lift" role="dialog">
        <div class="mb-2 flex items-center justify-between">
            <button type="button" @click="prev()" class="rounded-full p-1.5 hover:bg-surface-2" aria-label="Previous month"><x-icon name="chevron-left" size="16" /></button>
            <span class="text-sm font-semibold" x-text="title"></span>
            <button type="button" @click="next()" class="rounded-full p-1.5 hover:bg-surface-2" aria-label="Next month"><x-icon name="chevron-right" size="16" /></button>
        </div>
        <div class="grid grid-cols-7 text-center text-[11px] font-medium uppercase tracking-wider text-ink-faint">
            <template x-for="w in weekdays" :key="w"><span class="py-1" x-text="w"></span></template>
        </div>
        <div class="grid grid-cols-7 gap-0.5">
            <template x-for="d in days" :key="d.iso">
                <button type="button" @click="pick(d)" :disabled="d.disabled" :aria-label="d.iso" x-text="d.day"
                        class="h-8 rounded-lg text-sm transition disabled:cursor-not-allowed disabled:opacity-25"
                        :class="d.selected ? 'bg-accent font-semibold text-accent-fg' : (d.inMonth ? 'text-ink hover:bg-surface-2' : 'text-ink-faint hover:bg-surface-2') + (d.today ? ' ring-1 ring-inset ring-accent/60' : '')"></button>
            </template>
        </div>
        <div class="mt-2 flex items-center justify-between border-t border-line pt-2 text-xs">
            <button type="button" @click="today()" class="text-accent hover:underline">Today</button>
            <button type="button" @click="clear()" class="text-ink-faint hover:text-ink">Clear</button>
        </div>
    </div>
</div>
