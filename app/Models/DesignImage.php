<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignImage extends Model
{
    public const KIND_PHOTO = 'photo';

    public const KIND_PANORAMA = 'panorama';

    protected $fillable = ['design_id', 'url', 'width', 'height', 'kind', 'floor', 'pano_yaw', 'pano_pitch', 'pano_fov', 'title', 'description', 'angle', 'sort_order'];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'floor' => 'integer',
        'pano_yaw' => 'float',
        'pano_pitch' => 'float',
        'pano_fov' => 'float',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /** Marked as a 360° panorama by the admin. */
    public function isPanorama(): bool
    {
        return $this->kind === self::KIND_PANORAMA;
    }

    /**
     * Marked as a panorama and shaped like one. Images whose size is unknown
     * (for example external URLs) get the benefit of the doubt.
     */
    public function isViewablePanorama(): bool
    {
        return $this->isPanorama() && (! $this->width || ! $this->height || self::isTwoToOne($this->width, $this->height));
    }

    /** Equirectangular panoramas are twice as wide as they are tall. */
    public static function isTwoToOne(int $width, int $height): bool
    {
        return $height > 0 && abs($width / $height - 2) < 0.08;
    }
}
