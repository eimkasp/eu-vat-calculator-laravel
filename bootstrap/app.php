<?php

use App\Http\Middleware\AddLinkHeaders;
use App\Http\Middleware\AllowBackForwardCache;
use App\Http\Middleware\CollapseHtmlWhitespace;
use App\Http\Middleware\EmbedCookieFix;
use App\Http\Middleware\MarkdownNegotiation;
use App\Http\Middleware\PublicDiscoveryCacheHeaders;
use App\Http\Middleware\RedirectLegacySeoHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(PublicDiscoveryCacheHeaders::class);
        $middleware->prepend(AllowBackForwardCache::class);

        $middleware->throttleApi();

        $middleware->web(append: [
            RedirectLegacySeoHost::class,
            SecurityHeaders::class,
            SetLocale::class,
            AddLinkHeaders::class,
            AddLinkHeadersForPreloadedAssets::class,
            CollapseHtmlWhitespace::class,
            MarkdownNegotiation::class,
        ]);

        // Must be global (not web-group) so it runs AFTER StartSession and
        // EncryptCookies have already set the session cookie on the response.
        $middleware->append(EmbedCookieFix::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
