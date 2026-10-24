<?php

// backstage/laravel-og-image. App\Support\Seo\CountryOgImage draws the country page images with
// it; the package's own <x-og-image-tags> and og() helper use the same settings.
return [
    'debug' => env('OG_IMAGE_DEBUG', false), // disable caching og images for development

    'extension' => 'png', // jpg, png, webp

    'quality' => 90, // 0-100, for jpg and webp

    'width' => 1200,
    'height' => 630,

    'chrome' => [
        // A running Chrome's DevTools address (e.g. http://127.0.0.1:9222), started with
        // --remote-allow-origins=http://127.0.0.1, saves launching Chrome for every image.
        // Without it, or when it can't be reached, Chrome is launched from the path below.
        'url' => env('CHROME_URL'),
        'path' => env('CHROME_PATH', 'chromium'),
        'flags' => array_values(array_filter([
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--hide-scrollbars',
            '--mute-audio',
            '--font-render-hinting=none',
            '--force-color-profile=srgb',
            env('CHROME_NO_SANDBOX', false) ? '--no-sandbox' : null,
        ])),
    ],

    // Where og-image:warm puts the images to generate (null: the default connection and queue).
    'queue' => [
        'connection' => env('OG_IMAGE_QUEUE_CONNECTION'),
        'name' => env('OG_IMAGE_QUEUE'),
    ],

    // The cache location. The country images are served by CountryOgImageController, so they
    // live on the private local disk and need no storage:link.
    'storage' => [
        'disk' => env('OG_IMAGE_DISK', 'local'),
        'path' => 'og-images',
    ],

    'metatags' => [
        'og:title' => 'title',
        'og:description' => 'description',
        'og:type' => 'type',
        'og:url' => 'url',
    ],
];
