<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire marks every page that renders a component as no-store, which keeps public pages out of the browser's
 * back/forward cache. Public pages for guests hold nothing private, so they only need revalidating.
 */
class AllowBackForwardCache
{
    private const PRIVATE_PATHS = ['admin', 'admin/*', 'filament/*', 'pulse', 'pulse/*', 'livewire*'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable()
            || ! $response->isSuccessful()
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || ! str_contains((string) $response->headers->get('Cache-Control'), 'no-store')
            || $request->is(...self::PRIVATE_PATHS)
            || $request->user()) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-cache, private');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');

        return $response;
    }
}
