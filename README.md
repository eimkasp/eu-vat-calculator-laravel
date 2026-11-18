[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F516661be-8281-4b22-b780-b3a98db5eb8c%3Fdate%3D1%26commit%3D1&style=flat)](https://forge.laravel.com/servers/773218/sites/2296001)

# EU VAT Info

The most comprehensive, daily-updated source for VAT rates, calculators, validators, and historical data for all 27 EU member states. Built with Laravel 13, Livewire 4, Filament 5 and Tailwind CSS 4.

**Live site:** [eu-vat.info](https://eu-vat.info)

## Features

- **VAT Calculator** — Add or remove VAT for any EU country with real-time rates. Shareable calculation URLs.
- **VAT Number Validator** — Verify EU VAT numbers against the official VIES system with multi-layer caching (Redis → Database → VIES API).
- **Interactive VAT Map** — Accessible, keyboard-navigable choropleth of Europe coloured by standard rate, rendered on the server.
- **VAT Rate History** — Timeline of all VAT rate changes across EU member states with direction indicators.
- **Country Pages** — Dedicated pages for each of the 27 EU countries with rates, calculator, validator, and compliance guides.
- **Embeddable Widget** — Drop-in VAT calculator (vertical or horizontal) for third-party websites, with a live builder at `/embed`.
- **Command palette** — Press <kbd>⌘K</kbd> / <kbd>Ctrl K</kbd> or <kbd>/</kbd> anywhere to jump to a country or tool.
- **Light and dark themes** — System-aware theme with a manual override, built on semantic design tokens.
- **24 EU Languages** — Full i18n support with automated DeepL translations.
- **REST API** — JSON endpoints for countries, VAT rates, and VAT number validation.
- **AI/LLM Integration** — `llms.txt`, MCP server endpoint, and LLM-optimized JSON for AI agent consumption.
- **SEO Optimized** — Structured data (JSON-LD), multi-language XML sitemaps with hreflang, Open Graph tags.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Livewire 4, Alpine.js, Blade components, Tailwind CSS 4 (CSS-first tokens) |
| Admin | Filament 5 |
| Testing | Pest 4 / PHPUnit 12 |
| Database | MySQL (production), SQLite (testing) |
| API | Laravel Sanctum, REST JSON |
| i18n | 24 EU languages, DeepL API |
| SEO | Spatie Sitemap, hreflang, `llms.txt`, JSON-LD |
| Deploy | Laravel Forge, Vite 8 |
| Monitoring | Laravel Pulse |

## Supported Languages

All 24 official EU languages are supported. Translations are automated via DeepL where available.

| Code | Language | Code | Language | Code | Language |
|---|---|---|---|---|---|
| `en` | English | `fr` | French | `pl` | Polish |
| `bg` | Bulgarian | `ga` | Irish | `pt` | Portuguese |
| `cs` | Czech | `hr` | Croatian | `ro` | Romanian |
| `da` | Danish | `hu` | Hungarian | `sk` | Slovak |
| `de` | German | `it` | Italian | `sl` | Slovenian |
| `el` | Greek | `lt` | Lithuanian | `sv` | Swedish |
| `es` | Spanish | `lv` | Latvian | | |
| `et` | Estonian | `mt` | Maltese | | |
| `fi` | Finnish | `nl` | Dutch | | |

English is the default language and uses unprefixed URLs (`/vat-calculator/france`). All other languages use a locale prefix (`/de/vat-calculator/france`).

> **Note:** Irish (`ga`), Croatian (`hr`), and Maltese (`mt`) do not have DeepL support and use manual translations.

## Getting Started

### Requirements

- PHP 8.3+ with the `intl` extension
- Composer 2
- Node.js 20.19+ and Yarn
- MySQL 8+ (or SQLite for local development)

### Installation

```bash
# Clone the repository
git clone https://github.com/eimkasp/eu-vat.info.git
cd eu-vat.info

# Install PHP dependencies
composer install

# Install Node dependencies
yarn install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run database migrations and seed data
php artisan migrate
php artisan db:seed

# Build frontend assets
yarn build

# Start the development server
php artisan serve
```

### Development

```bash
# Start Vite dev server with HMR
yarn dev

# Run tests (add --parallel for a faster run)
php artisan test

# Code style (Laravel Pint)
./vendor/bin/pint

# Process queue jobs
php artisan queue:work

# Generate XML sitemap
php artisan sitemap:generate

# Clear caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear
```

## Architecture

### Directory Overview

```
app/
├── Console/Commands/        # Artisan commands
├── Filament/                # Admin panel (Filament 5)
├── Http/
│   ├── Controllers/         # Web + API controllers
│   ├── Middleware/           # SetLocale middleware
│   └── Resources/           # API resources
├── Jobs/                    # Queue jobs (VAT sync, translations, integrity checks)
├── Livewire/                # 24 reactive components (core UI)
├── Models/                  # 10 Eloquent models
├── Services/                # Business logic (analytics, VIES, translations, sitemap)
├── Support/                 # VAT maths (Support/Vat), navigation and map rendering
├── Traits/                  # HasAnalytics, TracksCountryViews
└── helpers.php              # Global helpers (locale_path, etc.)
resources/
├── css/app.css              # Tailwind 4 theme: semantic light/dark tokens and components
├── js/app.js, js/vat.js     # Alpine components and the client-side VAT maths
└── views/
    ├── livewire/            # Livewire Blade templates
    ├── components/          # Reusable Blade components (ui/, calculator/, …)
    └── layouts/             # App and embed layouts
lang/                        # 24 language directories
routes/
├── web.php                  # Localised routes
├── api.php                  # REST API endpoints
└── console.php              # Scheduled commands
```

### Key Models

| Model | Purpose |
|---|---|
| `Country` | EU country with VAT rates, currency, flags, ISO codes |
| `VatRate` | Historical rates by type (standard, reduced, super-reduced, parking) |
| `VatRateChange` | Tracks old→new rate changes with direction |
| `CountryAnalytic` | User interaction tracking (views, calculations) |
| `Translation` | Database-backed i18n strings |
| `VatValidationCache` | Cached VIES validation results |
| `VatValidationLog` | VIES API request audit log |
| `Banner` | Promotional banners with scheduling |
| `DataSource` | External data source metadata |

### Services

| Service | Purpose |
|---|---|
| `ViesValidationService` | Multi-layer VAT number validation: Redis → DB → VIES API, with fuzzy matching and confidence scoring |
| `TranslationService` | Translation chain: Cache → Database → DeepL API → English fallback |
| `CountryAnalyticsService` | IP geolocation and user interaction tracking |
| `SitemapGenerator` | Multi-language XML sitemap with hreflang alternates for all 24 locales |

### Background Jobs

| Job | Purpose |
|---|---|
| `UpdateVatRates` | Sync VAT rates from external data sources |
| `GenerateVatRateChanges` | Detect and record rate changes with old→new comparison |
| `TranslateText` | Async DeepL translation for missing language keys |
| `VerifyVatRatesIntegrity` | Data consistency and validation checks |

## API

All API responses use JSON. No authentication required for read endpoints.

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/countries` | All countries with every rate type, including multiple reduced rates |
| `GET` | `/api/v1/countries/{slug}` | Single country by slug or ISO code |
| `GET` | `/api/v1/calculate` | Calculate VAT (`amount`, `country`, `rate_type`, `mode=add\|remove`) |
| `GET` | `/api/v1/openapi.json` | OpenAPI 3.1 specification |
| `GET` | `/api/countries` | All countries with VAT rates (cached) |
| `GET` | `/api/countries/{slug}` | Single country details |
| `POST` | `/api/vat/validation/validate` | Validate a VAT number via VIES |
| `POST` | `/api/vat/validation/batch` | Batch validate up to 10 VAT numbers |
| `GET` | `/api/vat/validation/health` | VIES service health check |
| `GET` | `/api/llm/vat-rates` | LLM-optimized JSON with all rates |
| `POST` | `/api/mcp` | Model Context Protocol server (read-only) |

### Example

```bash
# Get all EU countries with VAT rates
curl https://eu-vat.info/api/countries

# Get VAT details for Germany
curl https://eu-vat.info/api/countries/germany

# Validate a VAT number
curl -X POST https://eu-vat.info/api/vat/validation/validate \
  -H "Content-Type: application/json" \
  -d '{"vat_number": "DE123456789"}'
```

## Data Management

### Full Data Sync

The `data:sync` command is the single entry point for keeping all data up to date. It orchestrates VAT rate refresh, country metadata validation, and change record generation.

```bash
# Full sync (VAT rates + country metadata + change records)
php artisan data:sync

# Preview what would change without modifying the database
php artisan data:sync --dry-run

# Sync only country metadata (currency, EU membership, VIES)
php artisan data:sync --skip-rates --skip-changes

# Sync only VAT rates, skip country metadata
php artisan data:sync --skip-country-data
```

**What it does:**

| Step | Description | Source |
|------|-------------|--------|
| 1. VAT Rates | Downloads latest rates from [kdeldycke/vat-rates](https://github.com/kdeldycke/vat-rates) and verifies integrity | GitHub CSV |
| 2. Country Metadata | Validates and fixes currency (code, name, symbol), EU membership, and VIES availability | [REST Countries API](https://restcountries.com) + hardcoded EU member list |
| 3. Change Records | Detects old→new rate changes and creates `VatRateChange` records for the history timeline | Database comparison |

The command includes a built-in fallback dataset for currency data in case the REST Countries API is unavailable.

### Individual Commands

```bash
# Validate country metadata only (dry-run by default)
php artisan countries:validate
php artisan countries:validate --fix

# Refresh VAT rates only
php artisan vat:refresh
php artisan vat:refresh --skip-download
php artisan vat:refresh --queue

# Verify VAT rate accuracy against known values
php artisan vat:verify
php artisan vat:verify --fix
```

### Scheduled Jobs

Data is kept fresh automatically via Laravel's scheduler (configured in `routes/console.php`):

| Schedule | Job | Description |
|----------|-----|-------------|
| Weekly (Mon 03:00) | `UpdateVatRates` | Download latest VAT rates from GitHub |
| Daily (04:00) | `VerifyVatRatesIntegrity` | Check data consistency |
| Daily (04:30) | `GenerateVatRateChanges` | Record any rate changes |

## AI & LLM Integration

The site is optimized for AI agent discovery and consumption:

- **`/llms.txt`** — Structured summary following the [llms.txt standard](https://llmstxt.org) for AI discoverability
- **`/llms-full.txt`** — Complete VAT rates in Markdown table format
- **`/api/llm/vat-rates`** — Machine-readable JSON with all countries, rates, ISO codes, and timestamps
- **`/api/mcp`** — [Model Context Protocol](https://modelcontextprotocol.io) endpoint for AI tool integration

## Embeddable Widget

Configure the widget, preview it live and copy the snippet at `/embed/{country?}`. The snippet embeds:

```html
<iframe src="https://eu-vat.info/public/embed/germany" title="EU VAT calculator" width="100%" height="760" style="border:0;background:transparent" loading="lazy"></iframe>
```

Append `?style=horizontal` for the wide layout (recommended height 500px). Unknown countries fall back to the default country so embeds never break.

## Data Sources

VAT rate data is sourced from the [European Commission](https://ec.europa.eu/taxation_customs/taxation/vat/how-vat-works/vat-rates_en) and the [kdeldycke/vat-rates](https://github.com/kdeldycke/vat-rates) dataset. Rates are synced automatically via background jobs.

## Admin Panel

The Filament 5 admin panel at `/admin` provides:

- Country and VAT rate management
- Analytics dashboard with usage statistics
- Banner management with scheduling
- Audit trail for all country data changes

## Security

If you discover a security vulnerability, please create an issue on [GitHub](https://github.com/eimkasp/eu-vat.info/issues).

## Mobile App

A native iOS and Android mobile app is available in the [`eu-vat-mobile/`](eu-vat-mobile/) directory, built with [NativePHP](https://nativephp.com) for Mobile.

| Feature | Description |
|---|---|
| VAT Rates | Browse all 27 EU countries with current rates |
| VAT Calculator | Add/remove VAT with country and rate type selection |
| VAT Validator | Verify VAT numbers against VIES |
| Platforms | iOS (Xcode 16+) and Android (Android Studio 2024.2.1+) |
| Stack | Laravel 13, Livewire 4, Tailwind CSS, NativePHP Mobile 3.2 |

The mobile app is a separate Laravel project that connects to the web app's REST API (`/api/countries`, `/api/vat/validation/validate`).

```bash
cd eu-vat-mobile
composer install && npm install
php artisan native:install
php artisan native:run
```

See [eu-vat-mobile/README.md](eu-vat-mobile/README.md) for full setup instructions.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
