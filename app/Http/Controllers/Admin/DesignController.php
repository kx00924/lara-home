<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\RoomType;
use App\Support\LocalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DesignController extends Controller
{
    public const ANGLES = ['Overview', 'Front view', 'Corner view', 'Detail', 'Window side', 'Night mood', 'Top view', 'Elevation', 'Floor plan'];

    public function index(Request $request)
    {
        $designs = Design::with(['category', 'roomType'])->withCount('images')
            ->search($request->query('q'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.designs.index', ['designs' => $designs, 'q' => $request->query('q')]);
    }

    public function create()
    {
        return $this->form(new Design(['published' => true, 'designer' => 'Home Studio', 'price' => 0]));
    }

    public function edit(Design $design)
    {
        $design->load('images');

        return $this->form($design);
    }

    private function form(Design $design)
    {
        return view('admin.designs.form', [
            'design' => $design,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'rooms' => RoomType::orderBy('sort_order')->orderBy('name')->get(),
            'angles' => self::ANGLES,
            'images' => $design->exists ? $design->images->map(fn ($i) => $i->only('id', 'url', 'kind', 'floor', 'pano_yaw', 'pano_pitch', 'pano_fov', 'title', 'description', 'angle'))->values() : collect(),
            'floors' => $design->exists ? $design->floorNames() : ['Ground floor'],
        ]);
    }

    public function store(Request $request)
    {
        $design = new Design;
        $warning = $this->save($request, $design);

        return redirect()->route('admin.designs.edit', $design)->with('success', 'Design created.')->with('info', $warning);
    }

    public function update(Request $request, Design $design)
    {
        $warning = $this->save($request, $design);

        return redirect()->route('admin.designs.edit', $design)->with('success', 'Design saved.')->with('info', $warning);
    }

    /** Saves the design and gallery; returns a warning about panoramas that are not 2:1, if any. */
    private function save(Request $request, Design $design): ?string
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'string'],
            'colors' => ['nullable', 'string'],
            'designer' => ['nullable', 'string', 'max:100'],
            'area_sqm' => ['nullable', 'integer', 'min:0'],
            'cover_image' => ['nullable', 'string', 'max:1000'],
            'floors' => ['nullable', 'array', 'max:10'],
            'floors.*' => ['nullable', 'string', 'max:60'],
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required', 'string', 'max:1000'],
            'images.*.kind' => ['nullable', 'in:photo,panorama'],
            'images.*.floor' => ['nullable', 'integer', 'min:1', 'max:10'],
            'images.*.pano_yaw' => ['nullable', 'numeric', 'between:-360,360'],
            'images.*.pano_pitch' => ['nullable', 'numeric', 'between:-90,90'],
            'images.*.pano_fov' => ['nullable', 'numeric', 'between:30,120'],
            'images.*.title' => ['nullable', 'string', 'max:200'],
            'images.*.description' => ['nullable', 'string'],
            'images.*.angle' => ['nullable', 'string', 'max:60'],
            'images.*.id' => ['nullable', 'integer'],
        ]);

        $images = collect($data['images'] ?? [])->values()->map(fn (array $img) => ['url' => LocalImage::relative($img['url'])] + $img);
        if (! empty($data['cover_image'])) {
            $data['cover_image'] = LocalImage::relative($data['cover_image']);
        }
        $firstPhoto = $images->first(fn (array $img) => ($img['kind'] ?? 'photo') !== DesignImage::KIND_PANORAMA) ?? $images->first();
        $floors = collect($data['floors'] ?? [])->map(fn ($name) => trim((string) $name))->filter()->values()->all();

        DB::transaction(function () use ($design, $data, $images, $firstPhoto, $floors, $request) {
            $design->fill([
                'title' => $data['title'],
                'summary' => $data['summary'] ?? '',
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'room_type_id' => $data['room_type_id'],
                'price' => (float) ($data['price'] ?? 0),
                'tags' => collect(explode(',', $data['tags'] ?? ''))->map(fn ($t) => strtolower(trim($t)))->filter()->unique()->values()->all(),
                'colors' => collect(explode(',', $data['colors'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all(),
                'designer' => ($data['designer'] ?? '') ?: 'Home Studio',
                'area_sqm' => (int) ($data['area_sqm'] ?? 0),
                'floors' => $floors ?: null,
                'featured' => $request->boolean('featured'),
                'published' => $request->boolean('published'),
                'cover_image' => ($data['cover_image'] ?? null) ?: ($firstPhoto['url'] ?? null),
            ]);
            $design->save();

            // Sync gallery: keep ids that are still present, update order/text, add new, delete removed.
            $keep = [];
            foreach ($images as $i => $img) {
                $image = ! empty($img['id']) ? DesignImage::where('design_id', $design->id)->find($img['id']) : null;
                $image ??= new DesignImage(['design_id' => $design->id]);
                $isPanorama = ($img['kind'] ?? 'photo') === DesignImage::KIND_PANORAMA;
                // Measure local files so only genuine 2:1 panoramas reach the 360° tour.
                if (! $image->exists || $image->url !== $img['url'] || ! $image->width) {
                    [$image->width, $image->height] = LocalImage::size($img['url']) ?? [null, null];
                }
                $image->fill([
                    'url' => $img['url'],
                    'kind' => $isPanorama ? DesignImage::KIND_PANORAMA : DesignImage::KIND_PHOTO,
                    'floor' => $isPanorama ? max(1, (int) ($img['floor'] ?? 1)) : null,
                    'pano_yaw' => $isPanorama ? (float) ($img['pano_yaw'] ?? 0) : 0,
                    'pano_pitch' => $isPanorama ? (float) ($img['pano_pitch'] ?? 0) : 0,
                    'pano_fov' => $isPanorama ? (float) ($img['pano_fov'] ?? 75) : 75,
                    'title' => $img['title'] ?? '',
                    'description' => $img['description'] ?? '',
                    'angle' => ($img['angle'] ?? '') ?: ($isPanorama ? '360° view' : 'Overview'),
                    'sort_order' => $i,
                ])->save();
                $keep[] = $image->id;
            }
            DesignImage::where('design_id', $design->id)->whereNotIn('id', $keep)->delete();
            if (! $design->cover_image && $firstPhoto) {
                $design->update(['cover_image' => $firstPhoto['url']]);
            }
        });
        Cache::forget('designs.tags');

        // 360° panoramas must be equirectangular (2:1); anything else looks distorted in the viewer.
        $misshapen = $images
            ->filter(fn (array $img) => ($img['kind'] ?? 'photo') === DesignImage::KIND_PANORAMA)
            ->filter(function (array $img) {
                $size = LocalImage::size($img['url']);

                return $size && $size[1] > 0 && abs($size[0] / $size[1] - 2) >= 0.08;
            })
            ->map(fn (array $img) => ($img['title'] ?? '') ?: basename((string) parse_url($img['url'], PHP_URL_PATH)));

        return $misshapen->isEmpty() ? null
            : $misshapen->count().' image(s) marked 360° are not in the 2:1 format 360° cameras produce, so they are left out of the 360° tour and shown as normal photos: '.$misshapen->implode(', ').'. Upload equirectangular images to use the tour.';
    }

    public function toggle(Request $request, Design $design)
    {
        $field = $request->input('field') === 'featured' ? 'featured' : 'published';
        $design->update([$field => ! $design->$field]);

        return back()->with('success', ucfirst($field).' updated.');
    }

    public function destroy(Request $request, Design $design)
    {
        $paid = $design->orders()->where('status', 'paid')->count();
        if ($paid && ! $request->boolean('force')) {
            return back()->with('error', "This design has {$paid} paid order(s). Unpublish it instead, or delete with force.");
        }
        $design->delete();
        Cache::forget('designs.tags');

        return redirect()->route('admin.designs.index')->with('success', 'Design deleted.');
    }

    public function recomputeTrending()
    {
        Design::all()->each(fn ($d) => $d->save());

        return back()->with('success', 'Trending scores recomputed.');
    }
}
