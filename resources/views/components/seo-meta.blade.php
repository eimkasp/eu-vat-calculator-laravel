@props([
    'title' => null,
    'description' => null,
    'url' => null,
    'image' => null,
    'type' => 'website',
    'robots' => null,
])

@php
    $seoPolicy = app(\App\Support\Seo\SeoPolicy::class);
    $title = \App\Support\Seo\MetaTitle::fit($title ?: __('ui.seo.default_title'), [__('ui.site_name')]);
    $description = $description ?: __('ui.seo.default_description');
    $resolvedRobots = $robots ?? $seoPolicy->robotsForCurrentLocale();
    $resolvedUrl = $seoPolicy->canonicalPageUrl($url ?: url()->current());
    $resolvedImage = $seoPolicy->canonicalizeLocalUrl($image ?: url('/images/og-default.png'));
@endphp

<!-- Primary Meta Tags -->
<title>{{ $title }}</title>
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $resolvedUrl }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:site_name" content="EU VAT Info">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ $resolvedUrl }}">
<meta property="twitter:title" content="{{ $title }}">
<meta property="twitter:description" content="{{ $description }}">
<meta property="twitter:image" content="{{ $resolvedImage }}">

<!-- Additional SEO -->
<link rel="canonical" href="{{ $resolvedUrl }}">
<meta name="robots" content="{{ $resolvedRobots }}">
<meta name="googlebot" content="{{ $resolvedRobots }}">

{{ $slot }}
