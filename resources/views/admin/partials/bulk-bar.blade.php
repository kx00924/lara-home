{{-- Bulk action bar for tables wrapped in x-data="bulkTable()". $action = route, $actions = [value => label] --}}
<form method="POST" action="{{ $action }}" @submit.prevent="run($el)" x-show="selected.length" x-cloak x-transition.opacity.duration.150ms data-no-loader
      class="sticky bottom-4 z-30 mt-4 flex flex-wrap items-center gap-3 rounded-xl3 border border-accent/40 bg-surface px-4 py-3 shadow-lift">
    @csrf
    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    <span class="text-sm font-medium"><span class="rounded-pill bg-accent px-2 py-0.5 text-xs text-accent-fg" x-text="selected.length"></span> selected</span>
    <select name="action" class="input !w-auto !py-1.5 !text-xs" aria-label="Bulk action">
        @foreach($actions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
    </select>
    <button class="btn-primary !py-1.5 !text-xs">Apply</button>
    <button type="button" @click="clear()" class="btn-ghost !py-1.5 !text-xs">Clear selection</button>
</form>
