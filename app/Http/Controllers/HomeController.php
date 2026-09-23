<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\RoomType;

class HomeController extends Controller
{
    public function __invoke()
    {
        $published = fn ($q) => $q->where('published', true);

        return view('home', [
            'categories' => Category::active()->withCount(['designs' => $published])->get(),
            'rooms' => RoomType::active()->withCount(['designs' => $published])->get(),
            'trending' => Design::published()->trending()->with(['category', 'roomType'])->limit(8)->get(),
            'featured' => Design::published()->where('featured', true)->trending()->with(['category', 'roomType'])->limit(3)->get(),
        ]);
    }
}
