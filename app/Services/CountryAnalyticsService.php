<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

use function Illuminate\Support\defer;

class CountryAnalyticsService
{
    /**
     * Records an interaction after the response has been sent, so the IP geolocation
     * lookup never delays page rendering.
     */
    public function trackView(Country $country, Request $request, string $type = 'view', array $metadata = []): void
    {
        $ip = $request->ip();
        $attributes = [
            'type' => $type,
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'amount' => $metadata['amount'] ?? null,
            'rate_used' => $metadata['rate_used'] ?? null,
            'meta_data' => $metadata !== [] ? $metadata : null,
        ];

        defer(fn () => $country->analytics()->create($attributes + self::locate($ip)));
    }

    /**
     * Looks an address up at most once a day (once an hour after a failed lookup), so repeat visits and every
     * calculation in a session reuse the first answer instead of calling the geolocation service again.
     *
     * @return array{location_country: ?string, location_city: ?string}
     */
    public static function locate(?string $ip): array
    {
        $unknown = ['location_country' => null, 'location_city' => null];

        if (! self::isPublicIp($ip)) {
            return $unknown;
        }

        $key = 'geoip:'.hash('sha256', $ip);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $location = rescue(fn () => Location::get($ip), null, false);
        $result = $location
            ? ['location_country' => $location->countryName ?: null, 'location_city' => $location->cityName ?: null]
            : $unknown;

        Cache::put($key, $result, $location ? now()->addDay() : now()->addHour());

        return $result;
    }

    /**
     * Private and reserved addresses (local development, tests, proxies without forwarded headers) cannot be
     * located, so they never cost a request to the geolocation service.
     */
    public static function isPublicIp(?string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
