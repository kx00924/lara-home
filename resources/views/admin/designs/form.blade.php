@extends('layouts.admin')
@section('title', $design->exists ? 'Edit design' : 'New design')

@section('content')
<form method="POST" action="{{ $design->exists ? route('admin.designs.update', $design) : route('admin.designs.store') }}" class="space-y-6"
      x-data="galleryManager(@js($images), @js($angles), @js(route('admin.upload')))" data-cover="{{ old('cover_image', $design->cover_image) }}">
    @csrf
    @if($design->exists) @method('PUT') @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.designs.index') }}" class="btn-ghost"><x-icon name="arrow-left" size="15" /> All designs</a>
        <div class="flex items-center gap-2">
            @if($design->exists)<a href="{{ route('designs.show', $design) }}" target="_blank" class="btn-ghost"><x-icon name="external" size="14" /> Preview</a>@endif
            <button class="btn-primary"><x-icon name="save" size="15" /> {{ $design->exists ? 'Save changes' : 'Create design' }}</button>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl2 bg-red-500/10 p-4 text-sm text-red-600"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <section class="card space-y-4 p-6">
                <h2 class="text-xl">Details</h2>
                <x-field label="Title" name="title"><input name="title" value="{{ old('title', $design->title) }}" class="input" required></x-field>
                <x-field label="Summary" name="summary" hint="One line shown on cards."><input name="summary" value="{{ old('summary', $design->summary) }}" class="input"></x-field>
                <x-field label="Description" name="description"><textarea name="description" class="input min-h-[140px]">{{ old('description', $design->description) }}</textarea></x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Design style" name="category_id"><select name="category_id" class="input">@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $design->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></x-field>
                    <x-field label="Room type" name="room_type_id"><select name="room_type_id" class="input">@foreach($rooms as $r)<option value="{{ $r->id }}" @selected(old('room_type_id', $design->room_type_id) == $r->id)>{{ $r->name }}</option>@endforeach</select></x-field>
                    <x-field label="Tags" name="tags" hint="Comma separated"><input name="tags" value="{{ old('tags', implode(', ', $design->tags ?? [])) }}" class="input"></x-field>
                    <x-field label="Designer" name="designer"><input name="designer" value="{{ old('designer', $design->designer) }}" class="input"></x-field>
                    <x-field label="Area (m²)" name="area_sqm"><input type="number" min="0" name="area_sqm" value="{{ old('area_sqm', $design->area_sqm) }}" class="input"></x-field>
                </div>
            </section>

            <section class="card p-6">
                <div class="mb-4">
                    <h2 class="text-xl">Gallery</h2>
                    <p class="text-sm text-ink-muted">Each image is shown as its own card with a title and description. The first image (or the starred one) is the cover; the rest are locked for paid designs.</p>
                </div>
                <div class="mb-5 flex flex-col gap-2 sm:flex-row">
                    <div class="flex flex-1 items-center gap-2 rounded-xl2 border border-line bg-surface px-3">
                        <x-icon name="link" size="15" class="text-ink-faint" />
                        <input x-model="urlInput" @keydown.enter.prevent="addUrl" placeholder="Paste an image URL and press Enter" class="w-full bg-transparent py-2.5 text-sm outline-none">
                        <button type="button" @click="addUrl" class="text-sm font-medium text-accent">Add</button>
                    </div>
                    <label class="btn-ghost cursor-pointer" :class="uploading ? 'opacity-60' : ''">
                        <x-icon name="upload" size="15" /> <span x-text="uploading ? 'Uploading…' : 'Upload files'"></span>
                        <input x-ref="file" type="file" accept="image/*" multiple class="hidden" @change="upload($event.target.files)">
                    </label>
                </div>

                <div x-show="images.length === 0" class="flex flex-col items-center rounded-xl2 border border-dashed border-line py-12 text-center text-sm text-ink-muted"><x-icon name="image" size="28" class="mb-2 text-ink-faint" /> No images yet. Add a URL or upload files.</div>
                <ul class="space-y-3">
                    <template x-for="(im, i) in images" :key="i + im.url">
                        <li class="grid gap-3 rounded-xl2 border border-line p-3 sm:grid-cols-[160px_1fr_auto]">
                            <input type="hidden" :name="`images[${i}][id]`" :value="im.id">
                            <input type="hidden" :name="`images[${i}][url]`" :value="im.url">
                            <div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-surface-2">
                                <img :src="im.url" alt="" class="h-full w-full object-cover" x-on:error="$el.style.opacity = 0.3">
                                <span class="absolute left-2 top-2 rounded-pill bg-black/50 px-2 py-0.5 text-[10px] text-white" x-text="'#' + (i + 1)"></span>
                                <span x-show="effectiveCover() === im.url" class="badge badge-accent absolute bottom-2 left-2">Cover</span>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[1fr_150px]">
                                <input :name="`images[${i}][title]`" x-model="im.title" placeholder="Image title" class="input !py-2">
                                <select :name="`images[${i}][angle]`" x-model="im.angle" class="input !py-2"><template x-for="a in angles" :key="a"><option :value="a" x-text="a"></option></template></select>
                                <textarea :name="`images[${i}][description]`" x-model="im.description" placeholder="Description shown under this image" class="input min-h-[64px] !py-2 sm:col-span-2"></textarea>
                            </div>
                            <div class="flex gap-1 sm:flex-col">
                                <button type="button" @click="move(i, -1)" class="rounded-lg p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Move up"><x-icon name="arrow-up" size="15" /></button>
                                <button type="button" @click="move(i, 1)" class="rounded-lg p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Move down"><x-icon name="arrow-down" size="15" /></button>
                                <button type="button" @click="cover = im.url" class="rounded-lg p-2 hover:bg-surface-2" :class="effectiveCover() === im.url ? 'text-amber-500' : 'text-ink-faint hover:text-ink'" aria-label="Set as cover"><x-icon name="star" size="15" /></button>
                                <button type="button" @click="remove(i)" class="rounded-lg p-2 text-ink-faint hover:bg-red-500/10 hover:text-red-500" aria-label="Remove"><x-icon name="trash" size="15" /></button>
                            </div>
                        </li>
                    </template>
                </ul>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card space-y-4 p-6">
                <h2 class="text-xl">Pricing & visibility</h2>
                <x-field label="Price" name="price" hint="0 makes the design free for everyone.">
                    <div class="flex items-center gap-2"><span class="text-ink-muted">{{ $site['currencySymbol'] }}</span><input type="number" min="0" step="1" name="price" value="{{ old('price', (int) $design->price) }}" class="input"></div>
                </x-field>
                <div class="flex items-center justify-between"><span class="text-sm">Published</span><x-toggle name="published" :checked="old('published', $design->published)" /></div>
                <div class="flex items-center justify-between"><span class="text-sm">Featured (editor's pick)</span><x-toggle name="featured" :checked="old('featured', $design->featured)" /></div>
            </section>
            <section class="card p-6">
                <h2 class="mb-3 text-xl">Cover preview</h2>
                <div class="aspect-[4/3] overflow-hidden rounded-xl2 bg-surface-2"><template x-if="effectiveCover()"><img :src="effectiveCover()" alt="" class="h-full w-full object-cover"></template></div>
                <x-field label="Cover image URL" class="mt-3"><input name="cover_image" x-model="cover" placeholder="Defaults to first gallery image" class="input"></x-field>
            </section>
        </aside>
    </div>
</form>
@endsection
