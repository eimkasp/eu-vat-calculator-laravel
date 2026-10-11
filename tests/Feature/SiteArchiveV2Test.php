<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
 * public/v2 is a frozen static snapshot of the 5.0 site, made by scripts/archive-site.mjs. These guards keep it
 * complete, self-contained, out of search results and free of anything that needs a server.
 */

function archiveV2Pages(): array
{
    static $pages;

    return $pages ??= collect(File::allFiles(public_path('v2')))
        ->filter(fn ($file) => $file->getFilename() === 'index.html')
        ->mapWithKeys(function ($file) {
            $directory = trim(str_replace('\\', '/', $file->getRelativePath()), '/');

            return [$directory === '' ? '/v2/' : '/v2/'.$directory.'/' => file_get_contents($file->getPathname())];
        })
        ->sortKeys()
        ->all();
}

function archiveV2References(string $html): array
{
    preg_match_all('/(?<=\s)(href|src|srcset|action)="([^"]*)"/', $html, $matches, PREG_SET_ORDER);

    return collect($matches)
        ->flatMap(fn (array $match) => $match[1] === 'srcset'
            ? collect(explode(',', html_entity_decode($match[2])))->map(fn (string $candidate) => ['src', strtok(trim($candidate), ' ')])
            : [[$match[1], html_entity_decode($match[2])]])
        ->all();
}

function archiveV2FileExists(string $url): bool
{
    $path = public_path(ltrim(strtok(strtok($url, '#'), '?'), '/'));

    return is_file($path) || is_file(rtrim($path, '/').'/index.html');
}

it('keeps a complete English snapshot of the 5.0 site', function () {
    $pages = collect(array_keys(archiveV2Pages()));
    $locales = implode('|', array_diff(array_keys(config('translation.supported_languages')), ['en']));

    expect($pages)->toHaveCount(290)
        ->toContain(
            '/v2/', '/v2/vat-calculator/', '/v2/vat-number-validator/', '/v2/vat-map/', '/v2/vat-changes/',
            '/v2/compare/germany-vs-france-vat/', '/v2/top-vat-calculations/1000/', '/v2/mcp-server/',
            '/v2/changelog/', '/v2/sitemap/', '/v2/styleguide/',
        )
        ->and($pages->filter(fn (string $page) => preg_match('#^/v2/vat-calculator/[a-z-]+/$#', $page))->count())->toBe(32)
        ->and($pages->filter(fn (string $page) => preg_match('#^/v2/vat-number-validator/[a-z-]+/$#', $page))->count())->toBe(27)
        ->and($pages->filter(fn (string $page) => preg_match('#^/v2/('.$locales.')/|^/v2/vat-calculation/#', $page)))->toBeEmpty();
});

it('marks every archived page as an unindexed, read-only snapshot', function () {
    $required = [
        '<meta name="robots" content="noindex, nofollow">',
        '<script src="/v2/archive.js"></script>',
        '<link rel="stylesheet" href="/v2/archive.css">',
        '<aside class="v2-archive-bar" aria-label="Archived version">',
        ' · Archive v2</title>',
        'data-csrf="archive" data-module-url="/v2/livewire" data-update-uri="/v2/livewire/update"',
    ];

    $forbidden = [
        'rel="canonical"' => 'archived pages must not claim a canonical URL',
        'hreflang=' => 'archived pages must not advertise alternates',
        'rel="alternate"' => 'machine-readable alternates belong to the live site',
        'rel="amphtml"' => 'AMP versions belong to the live site',
        'application/ld+json' => 'structured data belongs to the live site',
        'property="og:' => 'social previews belong to the live site',
        'name="csrf-token"' => 'the archive has no session',
        'serviceWorker' => 'the archive must not register a service worker',
        'modelContext' => 'the archive must not register WebMCP tools',
        'wire:navigate' => 'archived links load whole pages',
        '/livewire-' => 'the Livewire runtime is served from /v2/livewire',
        '127.0.0.1' => 'links must point at the archive or the live site',
        'localhost' => 'links must point at the archive or the live site',
    ];

    $violations = [];

    foreach (archiveV2Pages() as $page => $html) {
        foreach ($required as $snippet) {
            if (! str_contains($html, $snippet)) {
                $violations[] = "{$page} is missing {$snippet}";
            }
        }

        foreach ($forbidden as $snippet => $reason) {
            if (str_contains($html, $snippet)) {
                $violations[] = "{$page} contains {$snippet}: {$reason}";
            }
        }

        $live = substr($page, 3, -1) ?: '/';

        if (! str_contains($html, 'class="v2-archive-bar__link" href="https://vat.businesspress.io'.$live.'"')) {
            $violations[] = "{$page} does not link its archive bar to {$live} on the current site";
        }
    }

    expect($violations)->toBe([]);
});

it('serves every asset from inside the archive and resolves every archive link', function () {
    $violations = [];

    foreach (archiveV2Pages() as $page => $html) {
        foreach (archiveV2References($html) as [$attribute, $url]) {
            if ($attribute === 'src' && ! str_starts_with($url, '/v2/') && ! str_starts_with($url, 'data:')) {
                $violations[] = "{$page} loads {$url} from outside the archive";
            }

            if (str_starts_with($url, '/') && ! str_starts_with($url, '/v2/') && ! str_starts_with($url, '/v1/')) {
                $violations[] = "{$page} links to {$url}, which is neither archived nor on the live site";
            }

            if (str_starts_with($url, '/v2/') && ! archiveV2FileExists($url)) {
                $violations[] = "{$page} references missing {$url}";
            }
        }
    }

    $stylesheets = collect(File::files(public_path('v2/build/assets')))->filter(fn ($file) => $file->getExtension() === 'css');

    foreach ($stylesheets as $stylesheet) {
        preg_match_all('/url\(([^)]+)\)/', $stylesheet->getContents(), $urls);

        foreach ($urls[1] as $url) {
            $url = trim($url, '"\'');

            if (! str_starts_with($url, 'data:') && (! str_starts_with($url, '/v2/') || ! archiveV2FileExists($url))) {
                $violations[] = "{$stylesheet->getFilename()} loads {$url} from outside the archive";
            }
        }
    }

    expect($violations)->toBe([])
        ->and($stylesheets)->not->toBeEmpty()
        ->and(public_path('v2/archive.css'))->toBeFile()
        ->and(public_path('v2/archive.js'))->toBeFile()
        ->and(public_path('v2/livewire/livewire.min.js'))->toBeFile()
        ->and(public_path('v2/search-index.json'))->toBeFile();
});

it('answers the Livewire runtime in the browser and opens archived calculators', function () {
    $script = file_get_contents(public_path('v2/archive.js'));

    preg_match('/const calculators = new Set\((\[.*?\])\);/', $script, $calculators);
    preg_match('/const browserOnly = new Set\((\[.*?\])\);/', $script, $browserOnly);

    $archived = collect(array_keys(archiveV2Pages()))
        ->map(fn (string $page) => preg_match('#^/v2/vat-calculator/([a-z-]+)/$#', $page, $slug) ? $slug[1] : null)
        ->filter()
        ->values()
        ->all();

    expect($script)->toContain("const updatePath = '/v2/livewire/update';")
        ->toContain('Promise.reject(')
        ->and(json_decode($calculators[1] ?? 'null', true))->toBe($archived)
        ->and(json_decode(str_replace("'", '"', $browserOnly[1] ?? 'null'), true))->toBe(['calculate']);
});

it('keeps search working inside the archive', function () {
    $items = json_decode(file_get_contents(public_path('v2/search-index.json')), true);
    $violations = collect($items)
        ->pluck('url')
        ->reject(fn (string $url) => (str_starts_with($url, '/v2/') && archiveV2FileExists($url)) || str_starts_with($url, 'https://vat.businesspress.io/'))
        ->values()
        ->all();

    expect($items)->not->toBeEmpty()
        ->and($violations)->toBe([])
        ->and(archiveV2Pages()['/v2/'])->toContain('\/v2\/search-index.json');
});

it('links the archive from the live site without routing or indexing it', function () {
    $routes = collect(Route::getRoutes()->getRoutes())->map->uri();

    expect($routes->filter(fn (string $uri) => $uri === 'v2' || str_starts_with($uri, 'v2/')))->toBeEmpty();

    $this->get('/')->assertOk()->assertSee('href="/v2/"', false);
    $this->get('/changelog')->assertOk()->assertSee('href="/v2/"', false);
    $this->get('/sitemaps/core.xml')->assertOk()->assertDontSee('/v2', false);
    $this->get('/sitemaps/editorial.xml')->assertOk()->assertDontSee('/v2', false);
});
