@props(['name', 'model', 'label' => 'Image', 'hint' => null])
{{-- URL field plus "Upload file" button; `model` is the Alpine property of the parent scope that holds the URL. --}}
<div x-data="imageUpload(@js(route('admin.upload')))" {{ $attributes }}>
    <span class="label">{{ $label }}</span>
    <div class="flex gap-3">
        <button type="button" class="h-16 w-24 shrink-0 overflow-hidden rounded-xl2 bg-surface-2" @click="{{ $model }} && $dispatch('open-image', { url: {{ $model }}, title: @js($label) })" :class="{{ $model }} ? '' : 'cursor-default'">
            <template x-if="{{ $model }}"><img :src="{{ $model }}" alt="" class="h-full w-full object-cover"></template>
        </button>
        <div class="min-w-0 flex-1 space-y-2">
            <input name="{{ $name }}" x-model="{{ $model }}" placeholder="Paste an image URL or upload a file" class="input">
            <div class="flex flex-wrap items-center gap-2">
                <label class="btn-ghost cursor-pointer !px-3 !py-1.5 !text-xs" :class="busy ? 'opacity-60' : ''">
                    <x-icon name="upload" size="13" /> <span x-text="busy ? 'Uploading…' : 'Upload file'"></span>
                    <input type="file" accept="image/*" class="hidden" @change="send($event.target.files).then((u) => { if (u) {{ $model }} = u; $event.target.value = ''; })">
                </label>
                <button type="button" x-show="{{ $model }}" @click="{{ $model }} = ''" class="text-xs text-ink-faint hover:text-red-500">Remove</button>
            </div>
            @if($hint)<span class="block text-xs text-ink-faint">{{ $hint }}</span>@endif
        </div>
    </div>
</div>
