<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores uploaded gallery images on the public disk and returns their URLs
 * and dimensions (JSON). Images in the 2:1 equirectangular format used by
 * 360° cameras are flagged so the gallery manager can mark them as panoramas.
 */
class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['images' => ['required', 'array', 'max:12'], 'images.*' => ['image', 'max:51200']]);
        $files = [];
        foreach ($request->file('images') as $file) {
            [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];
            $path = $file->store('designs', 'public');
            $files[] = [
                // Site-relative, so the URL survives a change of domain (and is read from disk for downloads).
                'url' => '/storage/'.$path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'is_panorama' => self::looksLikePanorama($width, $height),
            ];
        }

        return response()->json(['files' => $files]);
    }

    /** Equirectangular panoramas are twice as wide as they are tall and reasonably large. */
    public static function looksLikePanorama(int $width, int $height): bool
    {
        return $height > 0 && $width >= 2000 && abs($width / $height - 2) < 0.08;
    }
}
