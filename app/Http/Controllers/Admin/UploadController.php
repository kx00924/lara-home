<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Stores uploaded gallery images on the public disk and returns their URLs (JSON). */
class UploadController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['images' => ['required', 'array', 'max:12'], 'images.*' => ['image', 'max:10240']]);
        $files = [];
        foreach ($request->file('images') as $file) {
            $path = $file->store('designs', 'public');
            $files[] = ['url' => asset('storage/'.$path), 'name' => $file->getClientOriginalName(), 'size' => $file->getSize()];
        }

        return response()->json(['files' => $files]);
    }
}
