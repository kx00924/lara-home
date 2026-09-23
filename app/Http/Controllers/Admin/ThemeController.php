<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $active = Setting::get('activeTheme', 'default');
        $themes = collect(available_themes())->map(fn (string $name) => array_merge([
            'name' => $name,
            'label' => ucfirst($name),
            'description' => 'Views in resources/views/themes/'.$name.'.',
            'swatches' => ['#111113', '#1d1d1f', '#ecebe8', '#ffffff'],
            'font' => '',
            'mode' => '',
        ], config('themes.'.$name, []), ['active' => $name === $active]));

        return view('admin.themes', [
            'themes' => $themes,
            'active' => $active,
            'previewing' => $request->session()->get('theme'),
        ]);
    }

    public function activate(Request $request, string $theme): RedirectResponse
    {
        abort_unless(in_array($theme, available_themes(), true), 404);
        Setting::put(['activeTheme' => $theme]);
        $request->session()->forget('theme');

        return redirect()->route('admin.themes.index')->with('success', ucfirst(config('themes.'.$theme.'.label', $theme)).' is now the live theme for all visitors.');
    }
}
