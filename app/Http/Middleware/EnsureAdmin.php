<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login', ['next' => $request->fullUrl()]);
        }
        if (! $request->user()->isAdmin()) {
            abort(403, __('messages.admin.admin_only'));
        }

        return $next($request);
    }
}
