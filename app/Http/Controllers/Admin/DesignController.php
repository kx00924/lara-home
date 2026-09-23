<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\RoomType;
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
            'images' => $design->exists ? $design->images->map(fn ($i) => $i->only('id', 'url', 'title', 'description', 'angle'))->values() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $design = new Design;
        $this->save($request, $design);

        return redirect()->route('admin.designs.edit', $design)->with('success', 'Design created.');
    }

    public function update(Request $request, Design $design)
    {
        $this->save($request, $design);

        return redirect()->route('admin.designs.edit', $design)->with('success', 'Design saved.');
    }

    private function save(Request $request, Design $design): void
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
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required', 'string', 'max:1000'],
            'images.*.title' => ['nullable', 'string', 'max:200'],
            'images.*.description' => ['nullable', 'string'],
            'images.*.angle' => ['nullable', 'string', 'max:60'],
            'images.*.id' => ['nullable', 'integer'],
        ]);

        $images = collect($data['images'] ?? [])->values();

        DB::transaction(function () use ($design, $data, $images, $request) {
            $design->fill([
                'title' => $data['title'],
                'summary' => $data['summary'] ?? '',
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'room_type_id' => $data['room_type_id'],
                'price' => (float) ($data['price'] ?? 0),
                'tags' => collect(explode(',', $data['tags'] ?? ''))->map(fn ($t) => strtolower(trim($t)))->filter()->unique()->values()->all(),
                'colors' => collect(explode(',', $data['colors'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all(),
                'designer' => $data['designer'] ?: 'Home Studio',
                'area_sqm' => (int) ($data['area_sqm'] ?? 0),
                'featured' => $request->boolean('featured'),
                'published' => $request->boolean('published'),
                'cover_image' => $data['cover_image'] ?: ($images->first()['url'] ?? null),
            ]);
            $design->save();

            // Sync gallery: keep ids that are still present, update order/text, add new, delete removed.
            $keep = [];
            foreach ($images as $i => $img) {
                $image = ! empty($img['id']) ? DesignImage::where('design_id', $design->id)->find($img['id']) : null;
                $image ??= new DesignImage(['design_id' => $design->id]);
                $image->fill([
                    'url' => $img['url'],
                    'title' => $img['title'] ?? '',
                    'description' => $img['description'] ?? '',
                    'angle' => $img['angle'] ?: 'Overview',
                    'sort_order' => $i,
                ])->save();
                $keep[] = $image->id;
            }
            DesignImage::where('design_id', $design->id)->whereNotIn('id', $keep)->delete();
            if (! $design->cover_image && $images->isNotEmpty()) {
                $design->update(['cover_image' => $images->first()['url']]);
            }
        });
        Cache::forget('designs.tags');
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
