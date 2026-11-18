<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicDiscoveryCacheHeaders
{
    private const ROUTE_MAX_AGE = [
        'acp-discovery' => 3600,
        'ads-txt' => 86400,
        'agent-skills-index' => 3600,
        'indexnow.key' => 86400,
        'sitemap' => 3600,
        'sitemap.generate' => 3600,
        'sitemap.section' => 3600,
        'vat-dataset.csv' => 3600,
        'vat-dataset.json' => 3600,
        'web-bot-auth' => 86400,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $routeName = $request->route()?->getName();

        if ($response->isSuccessful() && $routeName && isset(self::ROUTE_MAX_AGE[$routeName])) {
            $response->headers->set('Cache-Control', 'public, max-age='.self::ROUTE_MAX_AGE[$routeName]);
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
        }

        return $response;
    }
}
