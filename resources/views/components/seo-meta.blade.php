@props([
    'title' => null,
    'description' => null,
    'url' => null,
    'image' => null,
    'type' => 'website',
    'robots' => null,
    'canonical' => true,
])

@php
    $seoPolicy = app(\App\Support\Seo\SeoPolicy::class);
    $title = \App\Support\Seo\MetaTitle::fit($title ?: __('ui.seo.default_title'), [__('ui.site_name')]);
    $description = $description ?: __('ui.seo.default_description');
    $resolvedRobots = $robots ?? $seoPolicy->robotsForCurrentLocale();
    request()->attributes->set('seo.robots', $resolvedRobots);
    $resolvedUrl = $seoPolicy->canonicalPageUrl($url ?: url()->current());
    $resolvedImage = $seoPolicy->canonicalizeLocalUrl($image ?: url('/images/og-default.png'));
    $ogLocale = $seoPolicy->hasTranslatedBody() ? $seoPolicy->ogLocale() : $seoPolicy->ogLocale(config('translation.default_language', 'en'));
    $ogAlternates = $seoPolicy->shouldEmitHreflang()
        ? collect($seoPolicy->indexableLocales())->reject(fn (string $locale) => $locale === app()->getLocale())->map(fn (string $locale) => $seoPolicy->ogLocale($locale))
        : collect();
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
@unless($image)
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="EU VAT Info">
@endunless
<meta property="og:site_name" content="EU VAT Info">
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach($ogAlternates as $alternate)
    <meta property="og:locale:alternate" content="{{ $alternate }}">
@endforeach

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">

<!-- Additional SEO -->
@if($canonical)
    <link rel="canonical" href="{{ $resolvedUrl }}">
@endif
<meta name="robots" content="{{ $resolvedRobots }}">
<meta name="googlebot" content="{{ $resolvedRobots }}">

{{ $slot }}
