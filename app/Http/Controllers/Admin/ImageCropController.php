<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LocalImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Crops a gallery image at full resolution and stores the result as a new
 * file (the original is never overwritten). Returns the new URL and size.
 */
class ImageCropController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:1000'],
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
        ]);

        $bytes = $this->read($data['url']);
        abort_if($bytes === null, 422, 'The image could not be read. Only images on this site or public URLs can be cropped.');
        $info = @getimagesizefromstring($bytes);
        $source = $info ? @imagecreatefromstring($bytes) : false;
        abort_unless($source, 422, 'This file is not an image that can be cropped.');

        // Clamp the box to the image so an over-sized selection still produces a valid crop.
        [$width, $height] = [imagesx($source), imagesy($source)];
        $x = min($data['x'], $width - 1);
        $y = min($data['y'], $height - 1);
        $w = min($data['width'], $width - $x);
        $h = min($data['height'], $height - $y);

        $cropped = imagecrop($source, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
        imagedestroy($source);
        abort_unless($cropped, 500, 'The image could not be cropped.');

        $png = ($info['mime'] ?? '') === 'image/png';
        ob_start();
        if ($png) {
            imagesavealpha($cropped, true);
            imagepng($cropped, null, 6);
        } else {
            imagejpeg($cropped, null, 90);
        }
        imagedestroy($cropped);
        $path = 'designs/'.Str::random(40).($png ? '.png' : '.jpg');
        Storage::disk('public')->put($path, ob_get_clean());

        return response()->json([
            'url' => '/storage/'.$path,
            'width' => $w,
            'height' => $h,
            'is_panorama' => UploadController::looksLikePanorama($w, $h),
        ]);
    }

    private function read(string $url): ?string
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
}
