<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DesignController extends Controller
{
    public const SORTS = ['trending', 'newest', 'priceAsc', 'priceDesc', 'popular'];

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => array_filter(explode(',', (string) $request->query('category', ''))),
            'roomType' => array_filter(explode(',', (string) $request->query('roomType', ''))),
            'price' => in_array($request->query('price'), ['free', 'paid']) ? $request->query('price') : '',
            'tag' => array_filter(explode(',', (string) $request->query('tag', ''))),
            'sort' => in_array($request->query('sort'), self::SORTS) ? $request->query('sort') : 'trending',
            'featured' => $request->boolean('featured'),
        ];

        $query = Design::published()->with(['category', 'roomType'])->search($filters['q']);
        if ($filters['category']) {
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', $filters['category']));
        }
        if ($filters['roomType']) {
            $query->whereHas('roomType', fn ($q) => $q->whereIn('slug', $filters['roomType']));
        }
        if ($filters['price'] === 'free') {
            $query->where('price', '<=', 0);
        } elseif ($filters['price'] === 'paid') {
            $query->where('price', '>', 0);
        }
        foreach ($filters['tag'] as $tag) {
            $query->where('tags', 'like', '%"'.strtolower($tag).'"%');
        }
        if ($filters['featured']) {
            $query->where('featured', true);
        }
        match ($filters['sort']) {
            'newest' => $query->orderByDesc('created_at'),
            'priceAsc' => $query->orderBy('price')->orderByDesc('created_at'),
            'priceDesc' => $query->orderByDesc('price')->orderByDesc('created_at'),
            'popular' => $query->orderByDesc('views'),
            default => $query->trending(),
        };

        $designs = $query->paginate(12)->withQueryString();
        $published = fn ($q) => $q->where('published', true);

        return view('designs.index', [
            'designs' => $designs,
            'filters' => $filters,
            'categories' => Category::active()->withCount(['designs' => $published])->get(),
            'rooms' => RoomType::active()->withCount(['designs' => $published])->get(),
            'tags' => $this->popularTags(),
            'title' => $this->pageTitle($filters),
        ]);
    }

    public function show(Request $request, Design $design)
    {
        abort_unless($design->published || $request->user()?->isAdmin(), 404);
        Design::whereKey($design->id)->increment('views');
        Design::whereKey($design->id)->increment('trending_score');
        $design->load(['category', 'roomType', 'images']);
        $unlocked = $design->unlockedFor($request->user());

        return view('designs.show', [
            'design' => $design,
            'unlocked' => $unlocked,
            'similar' => $design->similar(6),
        ]);
    }

    /**
     * Zip every gallery image of a design the visitor may fully see
     * (free designs, purchased designs, or any design for admins).
     */
    public function download(Request $request, Design $design): BinaryFileResponse
    {
        abort_unless($design->published || $request->user()?->isAdmin(), 404);
        abort_unless($design->unlockedFor($request->user()), 403, 'Unlock this design to download its images.');

        $design->load('images');
        $tmp = tempnam(sys_get_temp_dir(), 'design-');
        $zip = new ZipArchive;
        abort_unless($zip->open($tmp, ZipArchive::OVERWRITE) === true, 500, 'Could not create the zip file.');

        $added = 0;
        foreach ($design->images as $i => $image) {
            $contents = $this->imageContents($image->url);
            if ($contents === null) {
                continue;
            }
            $name = sprintf('%02d-%s.jpg', $i + 1, Str::slug($image->angle ?: 'image'));
            $zip->addFromString($name, $contents);
            $added++;
        }
        $zip->addFromString('README.txt', "{$design->title}\n{$design->summary}\n\nDownloaded from ".setting('siteName')."\n");
        $zip->close();
        abort_if($added === 0, 404, 'No image files were available for this design.');

        return response()->download($tmp, $design->slug.'-images.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /** Raw bytes for a gallery image, whether it lives in public/, storage/ or on a remote host. */
    private function imageContents(string $url): ?string
    {
        if (preg_match('#^https?://#i', $url)) {
            $response = Http::timeout(30)->get($url);

            return $response->successful() ? $response->body() : null;
        }
        $path = public_path(ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/'));

        return is_file($path) ? file_get_contents($path) : null;
    }

    public function like(Design $design)
    {
        $design->increment('likes');
        $design->increment('trending_score', 5);

        return response()->json(['likes' => $design->likes]);
    }

    /** JSON suggestions for the header search box. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }
        $like = '%'.$q.'%';
        $designs = Design::published()->where('title', 'like', $like)->limit(5)->get(['title', 'slug', 'cover_image', 'price']);
        $cats = Category::active()->where('name', 'like', $like)->limit(3)->get(['name', 'slug']);
        $rooms = RoomType::active()->where('name', 'like', $like)->limit(3)->get(['name', 'slug']);

        return response()->json([
            ...$designs->map(fn ($d) => ['type' => 'design', 'label' => $d->title, 'url' => route('designs.show', $d), 'image' => thumb($d->cover_image, 120)]),
            ...$cats->map(fn ($c) => ['type' => 'style', 'label' => $c->name, 'url' => route('designs.index', ['category' => $c->slug])]),
            ...$rooms->map(fn ($r) => ['type' => 'room', 'label' => $r->name, 'url' => route('designs.index', ['roomType' => $r->slug])]),
        ]);
    }

    private function popularTags(): array
    {
        return Cache::remember('designs.tags', 600, function () {
            $counts = [];
            foreach (Design::published()->pluck('tags') as $tags) {
                foreach ($tags ?? [] as $t) {
                    $counts[$t] = ($counts[$t] ?? 0) + 1;
                }
            }
            arsort($counts);

            return array_slice($counts, 0, 18, true);
        });
    }

    private function pageTitle(array $f): string
    {
        if ($f['q'] !== '') {
            return '“'.$f['q'].'”';
        }
        $c = count($f['category']) === 1 ? Category::where('slug', $f['category'][0])->value('name') : null;
        $r = count($f['roomType']) === 1 ? RoomType::where('slug', $f['roomType'][0])->value('name') : null;
        if ($c && $r) {
            return "$c $r";
        }
        if ($c || $r) {
            return $c ?: $r;
        }
        if ($f['price'] === 'free') {
            return __('ui.designs.free_title');
        }
        if ($f['featured']) {
            return __('ui.home.featured');
        }

        return __('ui.designs.all_title');
    }
}
