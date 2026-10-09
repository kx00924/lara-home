{{-- Bulk action bar for tables wrapped in x-data="bulkTable()". $action = route, $actions = [value => label] --}}
<form method="POST" action="{{ $action }}" @submit.prevent="run($el)" data-no-loader
      class="mt-4 flex flex-wrap items-center gap-3 rounded-xl3 border bg-surface px-4 py-3 transition"
      :class="selected.length ? 'sticky bottom-4 z-30 border-accent/40 shadow-lift' : 'border-line'">
    @csrf
    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    <span class="text-sm" :class="selected.length ? 'font-medium' : 'text-ink-muted'">
        <span x-show="selected.length" class="rounded-pill bg-accent px-2 py-0.5 text-xs text-accent-fg" x-text="selected.length"></span>
        <span x-text="selected.length ? 'selected' : 'Bulk actions: tick the rows to change, then pick an action.'"></span>
    </span>
    <select name="action" class="input !w-auto !py-1.5 !text-xs" aria-label="Bulk action" :disabled="!selected.length">
        @foreach($actions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
    </select>
    <button class="btn-primary !py-1.5 !text-xs" :disabled="!selected.length">Apply</button>
    <button type="button" x-show="selected.length" @click="clear()" class="btn-ghost !py-1.5 !text-xs">Clear selection</button>
</form>
