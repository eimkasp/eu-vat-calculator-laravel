<?php

namespace App\Http\Middleware;

use App\Services\Seo\InternalLinkService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalizeVatComparison
{
    public function handle(Request $request, Closure $next): Response
    {
        $requested = explode('-vs-', (string) $request->route('pair'));
        $links = app(InternalLinkService::class);
        $canonical = $links->canonicalComparison($requested);

        // Only point at a page that exists; an unknown or repeated country falls through to the component's 404.
        if ($canonical && $requested !== $canonical && $links->comparableCountriesExist($canonical)) {
            return redirect()->to(locale_path(InternalLinkService::comparisonPath($canonical)), 301);
        }

        return $next($request);
    }
}
