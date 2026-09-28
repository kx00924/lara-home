@extends('layouts.admin')
@section('title', $design->exists ? 'Edit design' : 'New design')

@section('content')
<form method="POST" action="{{ $design->exists ? route('admin.designs.update', $design) : route('admin.designs.store') }}" class="space-y-6"
      x-data="galleryManager(@js($images), @js($angles), @js(route('admin.upload')), @js($floors), @js(route('admin.images.crop')))" data-cover="{{ old('cover_image', $design->cover_image) }}" @keydown.escape.window="preview !== null ? closePreview() : (crop.index !== null && closeCrop())">
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
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl">Floors</h2>
                        <p class="text-sm text-ink-muted">Each floor gets its own 360° viewer on the design page. Mark gallery images as 360° panoramas below and pick their floor.</p>
                    </div>
                    <button type="button" @click="addFloor()" class="btn-ghost" :disabled="floors.length >= 10"><x-icon name="plus" size="14" /> Add floor</button>
                </div>
                <ul class="space-y-2">
                    <template x-for="(name, n) in floors" :key="n">
                        <li class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface-2 text-sm font-semibold" x-text="n + 1"></span>
                            <input name="floors[]" x-model="floors[n]" maxlength="60" class="input !py-2" placeholder="Floor name, e.g. Ground floor">
                            <span class="shrink-0 whitespace-nowrap text-xs text-ink-faint" x-text="panoramaCount(n + 1) + ' panorama' + (panoramaCount(n + 1) === 1 ? '' : 's')"></span>
                            <button type="button" @click="removeFloor(n + 1)" :disabled="floors.length <= 1" class="rounded-lg p-2 text-ink-faint hover:bg-red-500/10 hover:text-red-500 disabled:opacity-30" aria-label="Remove floor"><x-icon name="trash" size="15" /></button>
                        </li>
                    </template>
                </ul>
            </section>

            <section class="card p-6">
                <div class="mb-4">
                    <h2 class="text-xl">Gallery</h2>
                    <p class="text-sm text-ink-muted">Each image is shown as its own card with a title and description. The first photo (or the starred one) is the cover; the rest are locked for paid designs. 360° panoramas must be equirectangular (twice as wide as tall, as exported by 360° cameras); uploads in that shape are marked as panoramas automatically.</p>
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
                <label class="-mt-3 mb-5 flex items-center gap-2 text-xs text-ink-muted"><input type="checkbox" x-model="cropAfterUpload" class="accent-accent"> Crop photos after uploading</label>

                <div x-show="images.length === 0" class="flex flex-col items-center rounded-xl2 border border-dashed border-line py-12 text-center text-sm text-ink-muted"><x-icon name="image" size="28" class="mb-2 text-ink-faint" /> No images yet. Add a URL or upload files.</div>
                <ul class="space-y-3">
                    <template x-for="(im, i) in images" :key="i + im.url">
                        <li class="grid gap-3 rounded-xl2 border border-line p-3 sm:grid-cols-[160px_1fr_auto]">
                            <input type="hidden" :name="`images[${i}][id]`" :value="im.id">
                            <input type="hidden" :name="`images[${i}][url]`" :value="im.url">
                            <input type="hidden" :name="`images[${i}][kind]`" :value="im.kind">
                            <input type="hidden" :name="`images[${i}][pano_yaw]`" :value="im.pano_yaw">
                            <input type="hidden" :name="`images[${i}][pano_pitch]`" :value="im.pano_pitch">
                            <input type="hidden" :name="`images[${i}][pano_fov]`" :value="im.pano_fov">
                            <div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-surface-2">
                                <img :src="im.url" alt="" class="h-full w-full object-cover" x-on:load="measure(im, $el)" x-on:error="$el.style.opacity = 0.3">
                                <span class="absolute left-2 top-2 rounded-pill bg-black/50 px-2 py-0.5 text-[10px] text-white" x-text="'#' + (i + 1)"></span>
                                <span x-show="im.kind === 'panorama'" class="badge badge-accent absolute right-2 top-2">360°</span>
                                <span x-show="effectiveCover() === im.url" class="badge badge-accent absolute bottom-2 left-2">Cover</span>
                            </div>
                            <div class="grid content-start gap-2 sm:grid-cols-[1fr_150px]">
                                <input :name="`images[${i}][title]`" x-model="im.title" :placeholder="im.kind === 'panorama' ? 'Room name, e.g. Living room' : 'Image title'" class="input !py-2">
                                <template x-if="im.kind !== 'panorama'">
                                    <select :name="`images[${i}][angle]`" x-model="im.angle" class="input !py-2"><template x-for="a in angles" :key="a"><option :value="a" x-text="a"></option></template></select>
                                </template>
                                <template x-if="im.kind === 'panorama'">
                                    <select :name="`images[${i}][floor]`" x-model.number="im.floor" class="input !py-2" aria-label="Floor"><template x-for="(name, n) in floors" :key="n"><option :value="n + 1" x-text="name || ('Floor ' + (n + 1))" :selected="im.floor === n + 1"></option></template></select>
                                </template>
                                <textarea :name="`images[${i}][description]`" x-model="im.description" placeholder="Description shown under this image" class="input min-h-[64px] !py-2 sm:col-span-2"></textarea>
                                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                                    <div class="inline-flex rounded-pill bg-surface-2 p-0.5 text-xs font-medium" role="radiogroup" aria-label="Image type">
                                        <button type="button" @click="setKind(im, 'photo')" class="rounded-pill px-3 py-1" :class="im.kind !== 'panorama' ? 'bg-surface shadow-soft' : 'text-ink-muted'" :aria-checked="im.kind !== 'panorama'" role="radio">Photo</button>
                                        <button type="button" @click="setKind(im, 'panorama')" class="rounded-pill px-3 py-1" :class="im.kind === 'panorama' ? 'bg-surface shadow-soft' : 'text-ink-muted'" :aria-checked="im.kind === 'panorama'" role="radio">360° panorama</button>
                                    </div>
                                    <template x-if="im.kind === 'panorama'">
                                        <button type="button" @click="openPreview(i)" class="btn-ghost !px-3 !py-1.5 text-xs"><x-icon name="view360" size="14" /> Set starting view</button>
                                    </template>
                                    <span x-show="im.kind === 'panorama'" class="text-[11px] text-ink-faint" x-text="`Starts at ${Math.round(im.pano_yaw)}° · tilt ${Math.round(im.pano_pitch)}° · zoom ${Math.round(im.pano_fov)}°`"></span>
                                    <span x-show="im.kind === 'panorama' && im.w > 0 && !isTwoToOne(im)" class="text-[11px] font-medium text-amber-600 dark:text-amber-400" x-text="`This image is ${im.w}×${im.h}, not 2:1. It will look stretched in the 360° viewer.`"></span>
                                    <span x-show="im.kind !== 'panorama' && isTwoToOne(im) && im.w >= 2000" class="text-[11px] text-ink-faint">Looks like a 360° panorama.</span>
                                </div>
                            </div>
                            <div class="flex gap-1 sm:flex-col">
                                <button type="button" @click="move(i, -1)" class="rounded-lg p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Move up"><x-icon name="arrow-up" size="15" /></button>
                                <button type="button" @click="move(i, 1)" class="rounded-lg p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Move down"><x-icon name="arrow-down" size="15" /></button>
                                <button type="button" @click="openCrop(i)" class="rounded-lg p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Crop" title="Crop"><x-icon name="crop" size="15" /></button>
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
                <x-field label="Cover image URL" class="mt-3"><input name="cover_image" x-model="cover" placeholder="Defaults to the first photo" class="input"></x-field>
            </section>
        </aside>
    </div>

    {{-- Crop dialog --}}
    <div x-cloak x-show="crop.index !== null" x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div class="flex max-h-[95vh] w-full max-w-5xl flex-col rounded-xl3 bg-surface p-5 shadow-lift">
            <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-xl">Crop image <span x-show="cropQueue.length" class="text-sm font-normal text-ink-faint" x-text="`· ${cropQueue.length} more after this`"></span></h3>
                    <p class="text-sm text-ink-muted">Drag the box to move it and its corners or edges to resize. The original file is kept; the crop is saved as a new image.</p>
                </div>
                <button type="button" @click="closeCrop()" class="rounded-full p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Close"><x-icon name="x" size="18" /></button>
            </div>

            <div class="mb-3 flex flex-wrap items-center gap-2">
                <template x-if="images[crop.index]?.kind !== 'panorama'">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="[label, value] in cropPresets" :key="label">
                            <button type="button" @click="setCropAspect(value)" class="chip" :class="crop.aspect === value ? 'chip-active' : ''" x-text="label"></button>
                        </template>
                    </div>
                </template>
                <span x-show="images[crop.index]?.kind === 'panorama'" class="text-xs text-ink-muted">360° panorama: locked to 2:1 so it still works in the viewer.</span>
                <span class="ml-auto font-mono text-xs text-ink-muted" x-show="crop.ready" x-text="`${cropPixels().width} × ${cropPixels().height} px of ${crop.natW} × ${crop.natH}`"></span>
            </div>

            <div class="flex min-h-0 flex-1 items-center justify-center overflow-hidden rounded-xl2 bg-black/60 p-2">
                <div x-ref="cropStage" class="relative inline-block max-h-full select-none overflow-hidden" x-show="crop.index !== null">
                    <template x-if="crop.index !== null">
                        <img :src="images[crop.index]?.url" alt="" draggable="false" class="block max-h-[65vh] max-w-full" x-on:load="cropImageLoaded($el)">
                    </template>
                    <div x-show="crop.ready" class="absolute cursor-move touch-none border-2 border-white shadow-[0_0_0_9999px_rgb(0_0_0/0.55)]"
                         :style="`left:${crop.x * 100}%; top:${crop.y * 100}%; width:${crop.w * 100}%; height:${crop.h * 100}%`"
                         @pointerdown="startCropDrag($event, 'move')">
                        <div class="pointer-events-none absolute inset-0 grid grid-cols-3 grid-rows-3">
                            <template x-for="n in 9" :key="n"><span class="border border-white/25"></span></template>
                        </div>
                        <template x-for="h in (crop.aspect ? ['nw', 'ne', 'sw', 'se'] : ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'])" :key="h">
                            <span @pointerdown.stop="startCropDrag($event, h)" class="absolute size-3.5 rounded-sm border-2 border-white bg-accent"
                                  :class="{ 'nw': '-left-2 -top-2 cursor-nwse-resize', 'ne': '-right-2 -top-2 cursor-nesw-resize', 'sw': '-bottom-2 -left-2 cursor-nesw-resize', 'se': '-bottom-2 -right-2 cursor-nwse-resize', 'n': '-top-2 left-1/2 -translate-x-1/2 cursor-ns-resize', 's': '-bottom-2 left-1/2 -translate-x-1/2 cursor-ns-resize', 'e': '-right-2 top-1/2 -translate-y-1/2 cursor-ew-resize', 'w': '-left-2 top-1/2 -translate-y-1/2 cursor-ew-resize' }[h]"></span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                <button type="button" x-show="cropQueue.length" @click="skipAllCrops()" class="btn-ghost mr-auto">Skip the rest</button>
                <button type="button" @click="setCropAspect(crop.aspect)" class="btn-ghost"><x-icon name="rotate" size="14" /> Reset</button>
                <button type="button" @click="closeCrop()" class="btn-ghost" x-text="cropQueue.length ? 'Skip this one' : 'Cancel'"></button>
                <button type="button" @click="applyCrop()" :disabled="!crop.ready || cropBusy" class="btn-primary"><x-icon name="crop" size="15" /> <span x-text="cropBusy ? 'Cropping…' : 'Apply crop'"></span></button>
            </div>
        </div>
    </div>

    {{-- 360° starting-view picker --}}
    <div x-cloak x-show="preview !== null" x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" @click.self="closePreview()">
        <div class="w-full max-w-4xl rounded-xl3 bg-surface p-5 shadow-lift">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-xl">Set the starting view</h3>
                    <p class="text-sm text-ink-muted">Drag to look around and scroll to zoom. Visitors see exactly this view when the panorama opens.</p>
                </div>
                <button type="button" @click="closePreview()" class="rounded-full p-2 text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label="Close"><x-icon name="x" size="18" /></button>
            </div>
            <div class="relative aspect-[16/9] overflow-hidden rounded-xl2 bg-black">
                <canvas x-ref="previewCanvas" tabindex="0" class="h-full w-full cursor-grab touch-none outline-none"></canvas>
                <div x-show="previewLoading" class="absolute inset-0 grid place-items-center text-sm text-white/80">Loading panorama…</div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <span class="font-mono text-xs text-ink-muted" x-text="`Direction ${Math.round(previewView.yaw)}° · tilt ${Math.round(previewView.pitch)}° · zoom ${Math.round(previewView.fov)}°`"></span>
                <div class="flex gap-2">
                    <button type="button" @click="closePreview()" class="btn-ghost">Cancel</button>
                    <button type="button" @click="useView()" class="btn-primary"><x-icon name="check" size="15" /> Use this view</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
