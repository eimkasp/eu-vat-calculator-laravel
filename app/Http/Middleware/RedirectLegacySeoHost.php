<?php

namespace App\Http\Middleware;

use App\Support\Seo\SeoPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacySeoHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getHost(), config('seo.legacy_hosts', []), true)) {
            $target = app(SeoPolicy::class)->canonicalHost().'/'.ltrim($request->getRequestUri(), '/');

            // 308 keeps the method and body of a POST (a Livewire update from a page opened on the old host).
            return redirect()->away($target, $request->isMethodSafe() ? 301 : 308);
        }

        return $next($request);
    }
}
