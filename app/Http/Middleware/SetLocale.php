<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const LOCALES = ['en' => 'English', 'ko' => '한국어', 'ja' => '日本語', 'zh' => '简体中文'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));
        if (array_key_exists($locale, self::LOCALES)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
