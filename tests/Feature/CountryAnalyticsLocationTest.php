<?php

use App\Services\CountryAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

function analyticsPosition(string $country, string $city): Position
{
    $position = new Position;
    $position->countryName = $country;
    $position->cityName = $city;

    return $position;
}

it('looks a public address up once and reuses the answer', function () {
    Location::shouldReceive('get')->once()->with('81.2.69.142')->andReturn(analyticsPosition('United Kingdom', 'London'));

    expect(CountryAnalyticsService::locate('81.2.69.142'))->toBe(['location_country' => 'United Kingdom', 'location_city' => 'London'])
        ->and(CountryAnalyticsService::locate('81.2.69.142'))->toBe(['location_country' => 'United Kingdom', 'location_city' => 'London']);
});

it('never looks up private addresses', function () {
    Location::shouldReceive('get')->never();

    expect(CountryAnalyticsService::locate('10.1.2.3'))->toBe(['location_country' => null, 'location_city' => null])
        ->and(CountryAnalyticsService::locate(null))->toBe(['location_country' => null, 'location_city' => null]);
});

it('remembers a failed lookup for an hour only', function () {
    Location::shouldReceive('get')->twice()->with('8.8.8.8')->andReturn(false, analyticsPosition('United States', 'Mountain View'));

    expect(CountryAnalyticsService::locate('8.8.8.8'))->toBe(['location_country' => null, 'location_city' => null])
        ->and(CountryAnalyticsService::locate('8.8.8.8'))->toBe(['location_country' => null, 'location_city' => null]);

    $this->travel(61)->minutes();

    expect(CountryAnalyticsService::locate('8.8.8.8'))->toBe(['location_country' => 'United States', 'location_city' => 'Mountain View']);
});

it('keeps a lookup failure from breaking tracking', function () {
    Location::shouldReceive('get')->once()->andThrow(new RuntimeException('Service unavailable'));

    expect(CountryAnalyticsService::locate('1.1.1.1'))->toBe(['location_country' => null, 'location_city' => null])
        ->and(Cache::has('geoip:'.hash('sha256', '1.1.1.1')))->toBeTrue();
});
