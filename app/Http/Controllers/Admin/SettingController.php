<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public const FONTS = ['Noto Serif', 'Noto Serif KR', 'Noto Serif JP', 'Georgia', 'Noto Sans', 'Noto Sans KR', 'Noto Sans JP', 'system-ui'];

    public function edit(Request $request)
    {
        return view('admin.settings', [
            's' => Setting::allValues(),
            'fonts' => self::FONTS,
            'themes' => available_themes(),
            'tab' => in_array($request->query('tab'), ['branding', 'theme', 'commerce']) ? $request->query('tab') : 'branding',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'siteName' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'heroTitle' => ['nullable', 'string', 'max:120'],
            'heroSubtitle' => ['nullable', 'string', 'max:500'],
            'heroImage' => ['nullable', 'string', 'max:1000'],
            'heroCtaText' => ['nullable', 'string', 'max:40'],
            'announcement' => ['nullable', 'string', 'max:200'],
            'footerText' => ['nullable', 'string', 'max:200'],
            'statDesigns' => ['nullable', 'string', 'max:20'],
            'statDesigners' => ['nullable', 'string', 'max:20'],
            'statCustomers' => ['nullable', 'string', 'max:20'],
            'activeTheme' => ['nullable', 'in:'.implode(',', available_themes())],
            'defaultTheme' => ['nullable', 'in:light,dark,system'],
            'accentColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'textColorLight' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'textColorDark' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'backgroundLight' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'backgroundDark' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'headingFont' => ['nullable', 'string', 'max:40'],
            'bodyFont' => ['nullable', 'string', 'max:40'],
            'borderRadius' => ['nullable', 'integer', 'min:0', 'max:40'],
            'currency' => ['nullable', 'string', 'size:3'],
            'currencySymbol' => ['nullable', 'string', 'max:5'],
            'contactEmail' => ['nullable', 'email'],
            'instagram' => ['nullable', 'string', 'max:300'],
            'pinterest' => ['nullable', 'string', 'max:300'],
            'youtube' => ['nullable', 'string', 'max:300'],
        ]);
        $data = array_map(fn ($v) => $v ?? '', $data);
        $data['allowUserThemeOverride'] = $request->boolean('allowUserThemeOverride');
        $data['borderRadius'] = (int) ($data['borderRadius'] ?: 16);
        $data['currency'] = strtoupper($data['currency'] ?: 'USD');
        Setting::put($data);

        return redirect()->route('admin.settings.edit', ['tab' => $request->input('tab', 'branding')])->with('success', 'Settings saved. The site updates immediately.');
    }
}
