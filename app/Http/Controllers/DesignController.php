<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\RoomType;
use App\Support\LocalImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        abort_unless(class_exists(ZipArchive::class), 500, 'Downloads need the PHP "zip" extension, which is not installed on this server.');
        $design->load('images');
        // Build the zip inside storage/: the system temp dir is often unwritable on shared hosting.
        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);
        $tmp = tempnam($tmpDir, 'design-') ?: tempnam(sys_get_temp_dir(), 'design-');
        abort_unless($tmp, 500, 'Could not create a temporary file for the zip.');
        $zip = new ZipArchive;
        abort_unless($zip->open($tmp, ZipArchive::OVERWRITE) === true, 500, 'Could not create the zip file.');

        // Layout: Photos/…, then one folder per floor under "360 panoramas/".
        $floorNames = $design->floorNames();
        $counters = [];
        $contents = [];
        foreach ($design->images as $image) {
            $bytes = $this->imageContents($image->url);
            if ($bytes === null) {
                Log::warning("Download of design {$design->slug}: image file not found, skipped.", ['url' => $image->url]);

                continue;
            }
            if ($image->isViewablePanorama()) {
                $floor = max(1, (int) $image->floor);
                $folder = '360 panoramas/'.$this->folderName("Floor {$floor} - ".($floorNames[$floor - 1] ?? "Floor {$floor}"));
                $label = $image->title ?: 'panorama';
            } else {
                $folder = 'Photos';
                $label = $image->title ?: ($image->angle ?: 'image');
            }
            $counters[$folder] = ($counters[$folder] ?? 0) + 1;
            $name = sprintf('%s/%02d-%s.%s', $folder, $counters[$folder], Str::slug(Str::limit($label, 60, '')) ?: 'image', $this->extension($image->url));
            $zip->addFromString($name, $bytes);
            $contents[$folder][] = basename($name);
        }
        if ($counters === []) {
            $zip->close();
            @unlink($tmp);
            abort(404, 'None of this design\'s image files were found on the server. Check that the files exist under public/ or storage/app/public and that "php artisan storage:link" has been run.');
        }

        $readme = "{$design->title}\n{$design->summary}\n\n";
        foreach ($contents as $folder => $files) {
            $readme .= "{$folder}/ (".count($files).")\n  ".implode("\n  ", $files)."\n\n";
        }
        if (collect(array_keys($counters))->contains(fn (string $folder) => str_starts_with($folder, '360 panoramas'))) {
            $readme .= "360 panoramas are equirectangular images: open them in any 360 photo viewer.\n\n";
        }
        $zip->addFromString('README.txt', $readme.'Downloaded from '.setting('siteName')."\n");
        $zip->close();

        return response()->download($tmp, $design->slug.'-images.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /**
     * Raw bytes for a gallery image. Files on this server are read from disk
     * (never fetched over HTTP, which would deadlock a single-threaded dev
     * server); only genuinely external URLs are downloaded.
     */
    private function imageContents(string $url): ?string
    {
        if ($path = LocalImage::path($url)) {
            return file_get_contents($path);
        }
        if (preg_match('#^https?://#i', $url) && ! LocalImage::isOwnHost($url)) {
            $response = Http::timeout(30)->get($url);

            return $response->successful() ? $response->body() : null;
        }

        return null;
    }

    private function extension(string $url): string
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true) ? $ext : 'jpg';
    }

    /** A folder name that is safe on Windows, macOS and Linux. */
    private function folderName(string $name): string
    {
        return trim(preg_replace('/[^\pL\pN ._-]+/u', ' ', $name) ?? 'Floor', ' .') ?: 'Floor';
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
