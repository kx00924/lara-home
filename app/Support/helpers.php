<?php

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('available_themes')) {
    /**
     * Theme names the site can render: "default" (resources/views) plus every
     * folder under resources/views/themes, each of which overrides only the views it contains.
     *
     * @return array<int, string>
     */
    function available_themes(): array
    {
        $dirs = glob(resource_path('views/themes/*'), GLOB_ONLYDIR) ?: [];

        return array_merge(['default'], array_map('basename', $dirs));
    }
}

if (! function_exists('active_theme')) {
    /** The theme rendering the current request (session preview beats the site setting). */
    function active_theme(): string
    {
        $theme = session('theme') ?: Setting::get('activeTheme', 'default');

        return in_array($theme, available_themes(), true) ? $theme : 'default';
    }
}

if (! function_exists('unique_slug')) {
    /**
     * URL slug for a model from its name or title, unique within its table.
     * Titles without Latin letters (Chinese, for example) would slug to an empty
     * string, so those get "<prefix>-<random>" instead of an empty, unroutable slug.
     */
    function unique_slug(Model $model, ?string $source, string $prefix): string
    {
        $base = Str::slug((string) $source) ?: $prefix.'-'.strtolower(Str::random(6));
        $slug = $base;
        $i = 1;
        while ($model->newQuery()->where('slug', $slug)->where($model->getKeyName(), '!=', $model->getKey() ?? 0)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}

if (! function_exists('price')) {
    /** "Free" for zero, otherwise the configured currency symbol + amount. */
    function price(float|int|null $amount): string
    {
        if (! $amount || $amount <= 0) {
            return __('ui.design.free');
        }
        $formatted = fmod((float) $amount, 1.0) == 0.0 ? number_format($amount) : number_format($amount, 2);

        return setting('currencySymbol', '$').$formatted;
    }
}

if (! function_exists('thumb')) {
    /**
     * Pick a smaller variant for thumbnails. Local library images get their
     * 640px twin when a small size is requested; Unsplash URLs are resized via
     * the query string; anything else is returned unchanged.
     */
    function thumb(?string $url, int $w = 800): string
    {
        if (! $url) {
            return '';
        }
        if (str_starts_with($url, '/images/library/') && $w <= 700) {
            $small = preg_replace('/\.jpg$/', '-640.jpg', $url);
            if ($small !== $url && file_exists(public_path($small))) {
                return $small;
            }
        }
        if (str_contains($url, 'images.unsplash.com')) {
            return preg_replace('/([?&])w=\d+/', '${1}w='.$w, $url);
        }

        return $url;
    }
}

if (! function_exists('compact_number')) {
    function compact_number(int|float|null $n): string
    {
        $n = (int) $n;
        if ($n >= 10000) {
            return round($n / 1000).'k';
        }
        if ($n >= 1000) {
            return number_format($n / 1000, 1).'k';
        }

        return (string) $n;
    }
}
