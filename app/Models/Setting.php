<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value site settings with defaults. Read with Setting::get('heroTitle') or
 * Setting::all() (merged with defaults); write with Setting::put([...]).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public const DEFAULTS = [
        'siteName' => 'Home Studio',
        'tagline' => 'Curated interior designs for calm, modern Asian living',
        'heroTitle' => 'Design a home that breathes.',
        'heroSubtitle' => 'Explore hundreds of photo-real interior concepts: Japandi, Zen Minimal, Modern Hanok and more. Preview free designs, unlock premium collections in every angle.',
        'heroImage' => '/images/library/photo-1616486338812-3dadae4b4ace.jpg',
        'heroCtaText' => 'Explore designs',
        'announcement' => '',
        'activeTheme' => 'default',
        'defaultTheme' => 'dark',
        'accentColor' => '#ffffff',
        'textColorLight' => '#1b1a19',
        'textColorDark' => '#ecebe8',
        'backgroundLight' => '#f8f7f5',
        'backgroundDark' => '#111113',
        'headingFont' => 'Noto Serif',
        'bodyFont' => 'Noto Sans',
        'borderRadius' => 16,
        'allowUserThemeOverride' => true,
        'currency' => 'USD',
        'currencySymbol' => '$',
        'footerText' => '© Home Studio. Crafted with calm.',
        'contactEmail' => 'hello@home.studio',
        'instagram' => 'https://instagram.com',
        'pinterest' => 'https://pinterest.com',
        'youtube' => 'https://youtube.com',
        'statDesigns' => '500+',
        'statDesigners' => '40',
        'statCustomers' => '12k',
    ];

    public static function allValues(): array
    {
        return Cache::rememberForever('site.settings', function () {
            $stored = static::query()->pluck('value', 'key')->map(fn ($v) => $v['v'] ?? null)->all();

            return array_merge(self::DEFAULTS, $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            static::updateOrCreate(['key' => $key], ['value' => ['v' => $value]]);
        }
        Cache::forget('site.settings');
    }
}
