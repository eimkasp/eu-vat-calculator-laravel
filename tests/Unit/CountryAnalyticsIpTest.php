<?php

use App\Services\CountryAnalyticsService;

it('only looks up public IP addresses', function (?string $ip, bool $public) {
    expect(CountryAnalyticsService::isPublicIp($ip))->toBe($public);
})->with([
    'loopback' => ['127.0.0.1', false],
    'private network' => ['10.1.2.3', false],
    'IPv6 loopback' => ['::1', false],
    'missing' => [null, false],
    'public IPv4' => ['81.2.69.142', true],
    'public IPv6' => ['2001:4860:4860::8888', true],
]);
