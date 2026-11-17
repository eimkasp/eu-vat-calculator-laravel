<?php

namespace App\Http\Middleware;

use App\Support\AdSense;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * External script sources required by the application.
     * - stats.businesspress.io: Plausible analytics
     * - consent.cookiebot.com: Cookiebot consent manager (optional, config-driven)
     * - cdn.ampproject.org: AMP runtime for AMP pages (/amp/*)
     *
     * 'unsafe-eval' is required by Alpine.js 3 which uses `new AsyncFunction`
     * internally for reactive expression evaluation (x-init, x-on, etc.).
     * 'unsafe-inline' is required by Livewire 3 for component hydration scripts.
     */
    private const SCRIPT_SOURCES = [
        "'self'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        'https://stats.businesspress.io',
        'https://consent.cookiebot.com',
        'https://cdn.ampproject.org',
    ];

    private const STYLE_SOURCES = [
        "'self'",
        "'unsafe-inline'", // Inline style attributes set by Livewire and Alpine
    ];

    private const IMG_SOURCES = [
        "'self'",
        'data:',
    ];

    private const CONNECT_SOURCES = [
        "'self'",
        'https://stats.businesspress.io', // Plausible beacon
        'https://cdn.ampproject.org',     // AMP runtime fetch calls
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $embeddable = in_array(AllowEmbedding::class, $request->route()?->gatherMiddleware() ?? [], true);

        // Clickjacking protection everywhere except the embeddable widget routes.
        if ($embeddable) {
            $response->headers->remove('X-Frame-Options');
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Force HTTPS for 1 year, include subdomains
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Stop leaking referrer info to third parties
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable browser features not used by this app
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Full Content-Security-Policy
        $response->headers->set('Content-Security-Policy', $this->buildCsp($embeddable));

        return $response;
    }

    private function buildCsp(bool $embeddable = false): string
    {
        // Google AdSense loads scripts, frames, images and beacons from Google's country domains
        // (adservice.google.lt, …) and from advertisers' hosts, which no fixed list covers, so
        // while AdSense is on these four directives allow any HTTPS source.
        $ads = AdSense::loadsScript() ? ['https:'] : [];

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', [...self::SCRIPT_SOURCES, ...$ads]),
            'style-src '.implode(' ', self::STYLE_SOURCES),
            'img-src '.implode(' ', [...self::IMG_SOURCES, ...$ads]),
            "font-src 'self'",
            'connect-src '.implode(' ', [...self::CONNECT_SOURCES, ...$ads]),
            'frame-src '.implode(' ', ["'self'", ...$ads]), // The embed widget preview, and ad frames while AdSense is on
            "object-src 'none'",   // No Flash/plugins
            "base-uri 'self'",     // Prevent base-tag injection
            $embeddable ? 'frame-ancestors *' : "frame-ancestors 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
