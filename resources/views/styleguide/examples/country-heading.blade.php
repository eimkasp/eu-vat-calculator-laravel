{{-- On a country page the heading is the h1 and the trail is the page's breadcrumb; nested here it steps down. --}}
<x-calculator.country-header :country="\App\Support\DesignSystem\ComponentCatalog::sampleCountry()" :heading-level="4" breadcrumb-label="Example breadcrumb" />
