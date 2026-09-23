<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\RoomType;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Downloads every remote image referenced by the catalogue into
 * public/images/library, writes a 640px thumbnail next to it and rewrites the
 * database references to the local files. Safe to re-run: existing files are kept.
 */
class LocalizeImages extends Command
{
    protected $signature = 'images:localize {--force : Re-download files that already exist}';

    protected $description = 'Download all remote catalogue images into public/images/library and point the database at them';

    public const DIR = 'images/library';

    public const THUMB_WIDTH = 640;

    private int $downloaded = 0;

    private int $rewritten = 0;

    private int $failed = 0;

    public function handle(): int
    {
        @mkdir(public_path(self::DIR), 0775, true);

        $hero = Setting::get('heroImage');
        if ($local = $this->localize($hero)) {
            Setting::put(['heroImage' => $local]);
            $this->rewritten++;
        }

        foreach (Category::all() as $c) {
            if ($local = $this->localize($c->image)) {
                $c->update(['image' => $local]);
                $this->rewritten++;
            }
        }
        foreach (RoomType::all() as $r) {
            if ($local = $this->localize($r->image)) {
                $r->update(['image' => $local]);
                $this->rewritten++;
            }
        }
        foreach (Design::all() as $d) {
            if ($local = $this->localize($d->cover_image)) {
                Design::whereKey($d->id)->update(['cover_image' => $local]); // skip model events
                $this->rewritten++;
            }
        }
        foreach (DesignImage::all() as $img) {
            if ($local = $this->localize($img->url)) {
                $img->update(['url' => $local]);
                $this->rewritten++;
            }
        }

        $this->info("Downloaded {$this->downloaded} file(s), rewrote {$this->rewritten} reference(s), {$this->failed} failure(s).");
        $this->line('Files live in public/'.self::DIR);

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    /** Returns the local path for a remote URL (downloading it if needed), or null if nothing to do. */
    private function localize(?string $url): ?string
    {
        if (! $url || ! preg_match('#^https?://#i', $url)) {
            return null;
        }
        $name = preg_match('#photo-([0-9a-f-]+)#i', $url, $m) ? 'photo-'.$m[1] : md5($url);
        $full = public_path(self::DIR."/{$name}.jpg");
        $thumb = public_path(self::DIR."/{$name}-".self::THUMB_WIDTH.'.jpg');

        if (! file_exists($full) || $this->option('force')) {
            $source = str_contains($url, 'images.unsplash.com')
                ? preg_replace('/([?&])w=\d+/', '${1}w=1600', $url)
                : $url;
            $this->line("  ↓ {$name}");
            try {
                $response = Http::timeout(60)->retry(2, 500)->get($source);
                if (! $response->successful()) {
                    throw new \RuntimeException('HTTP '.$response->status());
                }
                $image = @imagecreatefromstring($response->body());
                if (! $image) {
                    throw new \RuntimeException('not a decodable image');
                }
                imagejpeg($image, $full, 85);
                $this->writeThumb($image, $thumb);
                imagedestroy($image);
                $this->downloaded++;
            } catch (\Throwable $e) {
                $this->failed++;
                $this->error("  ✗ {$url}: {$e->getMessage()}");

                return null;
            }
        } elseif (! file_exists($thumb)) {
            $image = @imagecreatefromjpeg($full);
            if ($image) {
                $this->writeThumb($image, $thumb);
                imagedestroy($image);
            }
        }

        return '/'.self::DIR."/{$name}.jpg";
    }

    private function writeThumb(\GdImage $image, string $path): void
    {
        $w = imagesx($image);
        if ($w <= self::THUMB_WIDTH) {
            imagejpeg($image, $path, 82);

            return;
        }
        $scaled = imagescale($image, self::THUMB_WIDTH, -1, IMG_BICUBIC);
        imagejpeg($scaled, $path, 82);
        imagedestroy($scaled);
    }
}
