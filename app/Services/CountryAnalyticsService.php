<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Http\Request;
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

        defer(function () use ($country, $ip, $attributes) {
            $location = self::isPublicIp($ip) ? rescue(fn () => Location::get($ip), null, false) : null;

            $country->analytics()->create($attributes + [
                'location_country' => $location ? $location->countryName : null,
                'location_city' => $location ? $location->cityName : null,
            ]);
        });
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
