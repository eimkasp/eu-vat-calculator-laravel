<?php

namespace App\Support;

/**
 * Google AdSense, switched on by ADSENSE_ID: the ca-pub-… client ID shown in AdSense ("pub-…" works too).
 */
class AdSense
{
    /** Google's certification authority ID for ads.txt, the same for every publisher. */
    private const GOOGLE_CERTIFICATION_ID = 'f08c47fec0942fa0';

    public static function publisherId(): ?string
    {
        $id = trim((string) config('app.adsense_id'));

        return preg_match('/^(?:ca-)?(pub-\d{10,20})$/', $id, $match) ? $match[1] : null;
    }

    public static function clientId(): ?string
    {
        $publisher = self::publisherId();

        return $publisher ? 'ca-'.$publisher : null;
    }

    /**
     * The ad script runs in production only, so local and test traffic never requests ads.
     */
    public static function loadsScript(): bool
    {
        return self::publisherId() !== null && app()->isProduction();
    }

    public static function adsTxt(): ?string
    {
        $publisher = self::publisherId();

        return $publisher ? 'google.com, '.$publisher.', DIRECT, '.self::GOOGLE_CERTIFICATION_ID."\n" : null;
    }
}
