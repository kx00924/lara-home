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
 * Saves a cropped gallery image as a new file (the original is never
 * overwritten) and returns its URL and size.
 *
 * The browser normally crops the image itself and uploads the result, so no
 * image extension is needed on the server. Images it cannot read (external
 * URLs without CORS) are sent as a URL plus a pixel box and cropped here with
 * GD, when the extension is installed.
 */
class ImageCropController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required_without:url', 'image', 'max:51200'],
            'url' => ['required_without:image', 'string', 'max:1000'],
            'x' => ['required_with:url', 'integer', 'min:0'],
            'y' => ['required_with:url', 'integer', 'min:0'],
            'width' => ['required_with:url', 'integer', 'min:1'],
            'height' => ['required_with:url', 'integer', 'min:1'],
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];

            return $this->respond($file->store('designs', 'public'), $width, $height);
        }

        return $this->cropOnServer($data);
    }

    /**
     * @param  array{url: string, x: int, y: int, width: int, height: int}  $data
     */
    private function cropOnServer(array $data): JsonResponse
    {
        abort_unless(function_exists('imagecreatefromstring'), 422, 'This image could not be cropped in the browser, and the server has no GD image extension. Upload the image to this site first, then crop it.');

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

        return $this->respond($path, $w, $h);
    }

    private function respond(string $path, int $width, int $height): JsonResponse
    {
        return response()->json([
            'url' => '/storage/'.$path,
            'width' => $width,
            'height' => $height,
            'is_panorama' => UploadController::looksLikePanorama($width, $height),
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
