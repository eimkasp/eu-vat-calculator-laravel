<?php

/*
 * Back-end measurement for the EU VAT Info v3 benchmark: database queries, server time and Livewire snapshot
 * sizes per request. Each request runs in a fresh application instance, first with an empty cache (cold) and
 * then again with the cache it filled (warm), which is the state production serves from.
 *
 * Usage: php docs/benchmarks/v3-claude-opus-5-5/tools/backend.php /vat-map /de/vat-map … > backend.json
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;

$root = dirname(__DIR__, 4);

require $root.'/vendor/autoload.php';

function measureRequest(string $root, string $path): array
{
    Facade::clearResolvedInstances();
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $request = Request::create($path, 'GET', server: ['HTTP_ACCEPT' => 'text/html', 'REMOTE_ADDR' => '127.0.0.1']);

    $app->booted(function () {
        DB::enableQueryLog();
    });

    $started = hrtime(true);
    $response = $kernel->handle($request);
    $elapsed = (hrtime(true) - $started) / 1e6;
    $queries = DB::getQueryLog();
    $kernel->terminate($request, $response);

    $html = (string) $response->getContent();
    preg_match_all('/wire:snapshot="([^"]*)"/', $html, $matches);

    $snapshots = array_map(function (string $encoded) {
        $json = html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5);
        $snapshot = json_decode($json, true);

        return ['component' => $snapshot['memo']['name'] ?? 'unknown', 'bytes' => strlen($json)];
    }, $matches[1]);

    return [
        'status' => $response->getStatusCode(),
        'queries' => count($queries),
        'queryTimeMs' => round(array_sum(array_column($queries, 'time')), 2),
        'serverMs' => round($elapsed, 1),
        'htmlBytes' => strlen($html),
        'livewireComponents' => count($snapshots),
        'snapshotBytesTotal' => array_sum(array_column($snapshots, 'bytes')),
        'snapshots' => $snapshots,
    ];
}

$paths = array_slice($argv, 1);
$results = [];

foreach ($paths as $path) {
    Facade::clearResolvedInstances();
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Cache::flush();

    $cold = measureRequest($root, $path);
    $warm = measureRequest($root, $path);

    $results[] = ['path' => $path, 'cold' => $cold, 'warm' => $warm];
    fwrite(STDERR, sprintf("%s cold=%d warm=%d queries, snapshots=%d B\n", $path, $cold['queries'], $warm['queries'], $warm['snapshotBytesTotal']));
}

echo json_encode([
    'tool' => 'backend.php',
    'measuredAt' => gmdate('c'),
    'php' => PHP_VERSION,
    'environment' => [
        'app.env' => config('app.env'),
        'app.debug' => config('app.debug'),
        'database.default' => config('database.default'),
        'cache.default' => config('cache.default'),
    ],
    'pages' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
