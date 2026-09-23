<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a second theme override views. `?theme=name` previews a theme for the
 * session (`?theme=default` or `?theme=site` clears the preview); otherwise the
 * admin "Active theme" setting applies. The chosen theme's folder under
 * resources/views/themes is searched first, so it only needs the views it changes.
 */
class ApplyTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('theme')) {
            $requested = (string) $request->query('theme');
            if ($requested === 'site' || $requested === '') {
                $request->session()->forget('theme');
            } elseif (in_array($requested, available_themes(), true)) {
                $request->session()->put('theme', $requested);
            }
        }

        $theme = active_theme();
        $paths = config('view.paths');
        if ($theme !== 'default') {
            array_unshift($paths, resource_path('views/themes/'.$theme));
        }
        $finder = View::getFinder();
        $finder->setPaths($paths);
        $finder->flush(); // forget views resolved for a previous request/theme
        View::share('activeTheme', $theme);

        return $next($request);
    }
}
