<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'en';

        // Priority: 1. User preference, 2. Accept-Language header
        if ($request->user() && $request->user()->language) {
            $locale = $request->user()->language;
        } elseif ($request->hasHeader('Accept-Language')) {
            $locale = substr($request->header('Accept-Language'), 0, 2);
        }

        if (in_array($locale, ['en', 'si', 'ta'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
