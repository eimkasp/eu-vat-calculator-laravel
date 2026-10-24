<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Support\Seo\CountryOgImage;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Serves a country's social image, drawing it on the first request. The URL is versioned, so a
 * drawn image is cached for a year; when Chrome is unavailable the default image stands in for a
 * few minutes and the next request tries again.
 */
class CountryOgImageController
{
    public function __invoke(CountryOgImage $images, string $locale, string $slug, string $version): BinaryFileResponse|RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('translation.supported_languages', [])), 404);

        $country = Country::calculatorAvailable()->where('slug', $slug)->first();
        abort_unless($country, 404);

        $current = $images->version($country, $locale);

        if ($version !== $current) {
            return redirect(route('og-image.country', ['locale' => $locale, 'slug' => $slug, 'version' => $current], false), 301);
        }

        try {
            $path = $images->path($country, $locale);
            $maxAge = 31536000;
        } catch (Throwable $e) {
            report($e);
            $path = public_path('images/og-default.png');
            $maxAge = 300;
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age='.$maxAge.($maxAge > 300 ? ', immutable' : ''),
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
