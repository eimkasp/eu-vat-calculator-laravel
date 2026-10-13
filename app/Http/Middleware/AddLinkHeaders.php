<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddLinkHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->path() === '/' || $request->path() === '') {
            $links = [
                '</.well-known/api-catalog>; rel="api-catalog"',
                '</api/v1/openapi.json>; rel="service-desc"; type="application/vnd.oai.openapi+json"',
                '</llms.txt>; rel="service-doc"; type="text/plain"; title="LLM-friendly documentation"',
                '</.well-known/mcp/server-card.json>; rel="service-meta"; type="application/json"; title="MCP server card"',
                '</sitemap.xml>; rel="describedby"; type="application/xml"',
            ];

            $response->headers->set('Link', implode(', ', $links), false);
        }

        return $response;
    }
}
