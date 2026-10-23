<?php

use App\Http\Controllers\AmpController;
use App\Http\Controllers\Api\X402Controller;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SearchIndexController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\VatChangeSubscriptionController;
use App\Http\Controllers\VatDatasetDownloadController;
use App\Http\Controllers\WellKnownController;
use App\Http\Middleware\AllowEmbedding;
use App\Http\Middleware\CanonicalizeSharedCalculation;
use App\Http\Middleware\CanonicalizeVatComparison;
use App\Livewire\BlogIndex;
use App\Livewire\BlogShow;
use App\Livewire\BrandGuidelines;
use App\Livewire\Changelog;
use App\Livewire\ChromeExtension;
use App\Livewire\CountryVatCategory;
use App\Livewire\CountryVatHistory;
use App\Livewire\Donate;
use App\Livewire\Home;
use App\Livewire\HtmlSitemap;
use App\Livewire\McpServer;
use App\Livewire\PrivacyPolicy;
use App\Livewire\SharedCalculation;
use App\Livewire\Styleguide;
use App\Livewire\Tools;
use App\Livewire\TopCalculations;
use App\Livewire\TopCalculationsAmount;
use App\Livewire\VatCalculator;
use App\Livewire\VatCategoryHub;
use App\Livewire\VatCategoryIndex;
use App\Livewire\VatChangeEvent;
use App\Livewire\VatChangesArchive;
use App\Livewire\VatChangesHistory;
use App\Livewire\VatComparison;
use App\Livewire\VatComparisonIndex;
use App\Livewire\VatDataset;
use App\Livewire\VatMap;
use App\Livewire\VatScenarioGuide;
use App\Livewire\VatValidationApiDocs;
use App\Livewire\ViesValidatorPage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localised Routes
|--------------------------------------------------------------------------
| English (default): /vat-calculator, /country/germany, etc.
| Other languages:   /de/vat-calculator, /de/country/germany, etc.
*/

// Helper: register all app routes (used for both root and locale-prefix groups)
$registerRoutes = function () {
    Route::get('/', Home::class)->name('home');

    // 301 redirects: /country/{slug}[/{tab}] → /vat-calculator/{slug} (locale-aware)
    Route::get('/country/{slug}', [RedirectController::class, 'country'])->name('country.show');
    Route::get('/country/{slug}/{tab}', [RedirectController::class, 'country'])
        ->where('tab', 'vat-calculator|vat-validator|history|vat-guide|overview')
        ->name('country.tab');

    Route::get('/tools', Tools::class)->name('tools');

    Route::get('/vat-calculator', VatCalculator::class)->name('vat-calculator');
    Route::get('/vat-map', VatMap::class)->name('vat-map');
    Route::get('/vat-number-validator', ViesValidatorPage::class)->name('vies-validator');
    Route::get('/vat-number-validator/{slug}', ViesValidatorPage::class)->name('vies-validator.country');
    Route::get('/vat-validation-api', VatValidationApiDocs::class)->name('vat-validation-api');
    Route::get('/blog', BlogIndex::class)->name('blog.index');
    Route::get('/blog/{slug}', BlogShow::class)
        ->where('slug', '[a-z0-9\-]+')
        ->name('blog.show');

    // 301 redirect: /vat-calculator/{iso_code} → /vat-calculator/{slug} (e.g., /vat-calculator/mt → /vat-calculator/malta)
    Route::get('/vat-calculator/{code}', [RedirectController::class, 'isoCode'])
        ->where('code', '[a-zA-Z]{2}');

    Route::get('/vat-calculator/{slug}', VatCalculator::class)->name('vat-calculator.country');
    Route::get('/vat-calculation/{country}/{amount}/{rate}/{mode}', SharedCalculation::class)
        ->where(['amount' => '[0-9]+(\.[0-9]{1,2})?', 'rate' => '[0-9]+(\.[0-9]{1,2})?', 'mode' => 'exclude|include'])
        ->middleware(CanonicalizeSharedCalculation::class)
        ->name('shared-calculation');
    Route::get('/top-vat-calculations', TopCalculations::class)->name('top-calculations');
    Route::get('/top-vat-calculations/{amount}', TopCalculationsAmount::class)
        ->where('amount', '100|200|500|1000|2500|5000|10000')
        ->name('top-calculations.amount');
    Route::get('/vat-changes', VatChangesHistory::class)->name('vat-changes');
    Route::get('/vat-changes/upcoming', VatChangesArchive::class)
        ->defaults('archiveType', 'upcoming')
        ->name('vat-changes.upcoming');
    Route::get('/vat-changes/year/{year}', VatChangesArchive::class)
        ->whereNumber('year')
        ->name('vat-changes.year');
    Route::get('/vat-changes/{slug}/{rateType}/{date}', VatChangeEvent::class)
        ->where('rateType', 'standard|reduced|super_reduced|parking|zero')
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')
        ->name('vat-changes.event');
    Route::get('/vat-rates/categories', VatCategoryIndex::class)->name('vat-rates.categories');
    Route::get('/vat-rates/categories/{category}', VatCategoryHub::class)
        ->where('category', '[a-z0-9-]+')
        ->name('vat-rates.category');
    Route::get('/vat-rates/{slug}/history', CountryVatHistory::class)->name('vat-rates.country-history');
    Route::get('/vat-rates/{country}/categories/{category}', CountryVatCategory::class)
        ->where(['country' => '[a-z0-9-]+', 'category' => '[a-z0-9-]+'])
        ->name('vat-rates.country-category');
    Route::get('/compare', VatComparisonIndex::class)->name('vat-comparisons');
    Route::get('/compare/{pair}-vat', VatComparison::class)
        ->where('pair', '[a-z0-9-]+-vs-[a-z0-9-]+')
        ->middleware(CanonicalizeVatComparison::class)
        ->name('vat-comparison');
    Route::get('/vat-guides/{scenario}', VatScenarioGuide::class)
        ->where('scenario', '[a-z0-9-]+')
        ->name('vat-scenario-guide');
    Route::get('/changelog', Changelog::class)->name('changelog');
    Route::get('/mcp-server', McpServer::class)->name('mcp-server');
    Route::get('/styleguide', Styleguide::class)->name('styleguide');
    Route::get('/brand', BrandGuidelines::class)->name('brand');
    Route::get('/chrome-extension', ChromeExtension::class)->name('chrome-extension');
    Route::get('/donate', Donate::class)->name('donate');
    Route::get('/privacy', PrivacyPolicy::class)->name('privacy');
    Route::get('/sitemap', HtmlSitemap::class)->name('html-sitemap');
    Route::get('/datasets/eu-vat-rates', VatDataset::class)->name('vat-dataset');
};

// Locale-prefixed routes for non-default languages (registered FIRST)
$supported = array_keys(config('translation.supported_languages', []));
$nonDefault = array_filter($supported, fn ($l) => $l !== config('translation.default_language', 'en'));
$localePattern = implode('|', $nonDefault);

Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->name('locale.')
    ->group($registerRoutes);

// Default locale (en) — no prefix (registered LAST so names point to root URIs)
$registerRoutes();

// Legacy redirect: /calculation?country=X&amount=Y&rate=Z&mode=M → /vat-calculation/X/Y/Z/M
Route::get('/calculation', [RedirectController::class, 'legacyCalculation']);

// Language switch route
Route::get('/lang/{locale}', [LangController::class, 'switch'])->name('lang.switch');

Route::get('/vat-change-alerts/unsubscribe/{subscription}', [VatChangeSubscriptionController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('vat-change-alerts.unsubscribe');

// Non-localised routes (sitemap, embed, API, etc.)
Route::get('/sitemap/generate', [SitemapController::class, 'index'])->name('sitemap.generate');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/datasets/eu-vat-rates.csv', [VatDatasetDownloadController::class, 'csv'])->name('vat-dataset.csv');
Route::get('/datasets/eu-vat-rates.json', [VatDatasetDownloadController::class, 'json'])->name('vat-dataset.json');
Route::get('/sitemaps/{section}.xml', [SitemapController::class, 'section'])
    ->where('section', 'core|countries|validators|changes|calculations|categories|editorial')
    ->name('sitemap.section');

// RFC 9727 — API Catalog
Route::get('/.well-known/api-catalog', [WellKnownController::class, 'apiCatalog'])->name('api-catalog');

// RFC 8414 — OAuth 2.0 Authorization Server Metadata
Route::get('/.well-known/oauth-authorization-server', [WellKnownController::class, 'oauthAuthorizationServer'])->name('oauth-discovery');

// RFC 7517 — JSON Web Key Set
Route::get('/.well-known/jwks.json', [WellKnownController::class, 'jwks'])->name('jwks');

// IETF WebBotAuth — HTTP Message Signatures Directory
Route::get('/.well-known/http-message-signatures-directory', [WellKnownController::class, 'httpMessageSignaturesDirectory'])->name('web-bot-auth');

// Agent Skills Discovery Index (RFC v0.2.0)
Route::get('/.well-known/agent-skills/index.json', [WellKnownController::class, 'agentSkillsIndex'])->name('agent-skills-index');
Route::redirect('/.well-known/agent-skills', '/.well-known/agent-skills/index.json', 301);

// Agent skill files (text/markdown) for AI agent discovery
Route::get('/.well-known/agent-skills/{skill}/SKILL.md', [WellKnownController::class, 'agentSkill'])
    ->where('skill', '[a-z0-9\-]+')
    ->name('agent-skill');

// RFC 9728 — OAuth Protected Resource Metadata
Route::get('/.well-known/oauth-protected-resource', [WellKnownController::class, 'oauthProtectedResource'])->name('oauth-protected-resource');

// SEP-1649 — MCP Server Card for agent discovery
Route::get('/.well-known/mcp/server-card.json', [WellKnownController::class, 'mcpServerCard'])->name('mcp-server-card');

// ACP Discovery Document — Agentic Commerce Protocol
Route::get('/.well-known/acp.json', [WellKnownController::class, 'acpDiscovery'])->name('acp-discovery');

// AMP pages — lightweight static HTML for Google AMP index
// Canonical pages have <link rel="amphtml"> pointing to these URLs
Route::prefix('amp')->name('amp.')->group(function () {
    Route::get('/', [AmpController::class, 'home'])->name('home');
    Route::get('/vat-rates', [AmpController::class, 'vatRates'])->name('vat-rates');
    Route::get('/vat-calculator/{slug}', [AmpController::class, 'country'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('country');
});

Route::middleware(AllowEmbedding::class)->group(function () {
    Route::get('/embed/{country?}', [EmbedController::class, 'index'])->where('country', '[a-z0-9\-]+')->name('widget.embed');
    Route::get('/public/embed/{country?}', [EmbedController::class, 'iframe'])->where('country', '[a-z0-9\-]+')->name('widget.iframe');
});

Route::get('/search-index.json', SearchIndexController::class)->name('search-index');

// Machine-readable design system: DTCG design tokens and DESIGN.md
Route::get('/styleguide/design.tokens.json', [StyleguideController::class, 'tokens'])->name('styleguide.tokens');
Route::get('/styleguide.md', [StyleguideController::class, 'markdown'])->name('styleguide.markdown');
Route::get('/brand/eu-vat-info-mark.svg', [StyleguideController::class, 'mark'])->name('brand.mark');

// LLM-optimised site map and full VAT rates table
Route::get('/llms.txt', [LlmsController::class, 'index']);
Route::get('/llms-full.txt', [LlmsController::class, 'fullTxt']);

Route::get('/indexnow-key.txt', function () {
    abort_unless(config('seo.indexnow.enabled') && config('seo.indexnow.key'), 404);

    return response((string) config('seo.indexnow.key'))
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('indexnow.key');

// Bazaar-compatible x402 resource discovery (scanner probes this path)
Route::get('/platform/v2/x402/discovery/resources', [X402Controller::class, 'discoveryResources']);
