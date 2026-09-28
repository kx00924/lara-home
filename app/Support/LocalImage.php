<?php

namespace App\Support;

/**
 * Maps image URLs to files on this server. Images live under public/ (the
 * library and the storage link); their URLs may be relative ("/storage/...")
 * or absolute with this site's own host ("http://localhost:8000/storage/...").
 */
class LocalImage
{
    /** Absolute path of the local file behind a URL, or null when it is not one of ours. */
    public static function path(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('#^https?://#i', $url) && ! self::isOwnHost($url)) {
            return null;
        }
        $relative = ltrim(rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?: '')), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }
        $path = public_path($relative);

        return is_file($path) ? $path : null;
    }

    /** Strips this site's own origin so stored URLs keep working when the domain changes. */
    public static function relative(string $url): string
    {
        if (preg_match('#^https?://#i', $url) && self::isOwnHost($url)) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);

            return $path.($query ? '?'.$query : '');
        }

        return $url;
    }

    /** Pixel size of a local image, or null when unknown. @return array{0: int, 1: int}|null */
    public static function size(?string $url): ?array
    {
        $path = self::path($url);
        $info = $path ? @getimagesize($path) : false;

        return $info ? [(int) $info[0], (int) $info[1]] : null;
    }

    public static function isOwnHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $own = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            app()->bound('request') ? strtolower(request()->getHost()) : null,
            'localhost',
            '127.0.0.1',
        ]);

        return in_array($host, $own, true);
    }
}
