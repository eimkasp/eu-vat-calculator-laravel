<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\VatValidationLog;
use App\Services\ViesValidationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ViesValidatorPage extends Component
{
    public const NUMBER_PATTERN = '/^[0-9A-Z+*]{2,14}$/';

    private const EXAMPLE = ['LT', '100019070512'];

    private const LOOKUPS_PER_MINUTE = 20;

    #[Url(except: '')]
    public string $country_code = '';

    #[Url(except: '')]
    public string $vat_number = '';

    #[Locked]
    public ?string $slug = null;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public ?string $error = null;

    public function mount(?string $slug = null): void
    {
        if ($slug !== null) {
            $this->slug = $slug;
            abort_unless($this->pageCountry, 404);

            $this->country_code = collect($this->countries)->firstWhere('slug', $slug)['iso'] ?? '';
        }

        $this->normalize();

        if ($this->country_code !== '' && $this->vat_number !== '' && $this->inputIsWellFormed()) {
            $this->validateVat(app(ViesValidationService::class));
        }
    }

    /**
     * @return list<array{iso: string, prefix: string, name: string, slug: string}>
     */
    #[Computed]
    public function countries(): array
    {
        return Cache::remember('vies_validator_countries_v1', 3600, fn () => Country::query()
            ->where('is_eu_member', true)
            ->where('vies_available', true)
            ->orderBy('name')
            ->get(['name', 'slug', 'iso_code'])
            ->map(fn (Country $country) => [
                'iso' => strtoupper((string) $country->iso_code),
                'prefix' => strtoupper((string) $country->iso_code) === 'GR' ? 'EL' : strtoupper((string) $country->iso_code),
                'name' => $country->name,
                'slug' => $country->slug,
            ])
            ->values()
            ->all());
    }

    #[Computed]
    public function pageCountry(): ?Country
    {
        return $this->slug ? Country::query()->where('slug', $this->slug)->first() : null;
    }

    #[Computed]
    public function selectedCountry(): ?array
    {
        return collect($this->countries)->firstWhere('iso', $this->country_code);
    }

    public function validateVat(ViesValidationService $service): void
    {
        $this->reset('result', 'error');
        $this->normalize();

        $this->validate([
            'country_code' => ['required', Rule::in(array_column($this->countries, 'iso'))],
            'vat_number' => ['required', 'regex:'.self::NUMBER_PATTERN],
        ], [
            'country_code' => __('ui.vies_page.unsupported_country'),
            'vat_number' => __('ui.vies_page.invalid_format'),
        ]);

        $limiterKey = 'vies-page:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::LOOKUPS_PER_MINUTE)) {
            $this->error = __('ui.vies_page.rate_limited');

            return;
        }

        RateLimiter::hit($limiterKey, 60);

        $data = $service->validate($this->country_code, $this->vat_number);

        if (isset($data['error'])) {
            $code = (string) ($data['error_code'] ?? '');
            $this->error = $code === 'INVALID_INPUT' || str_starts_with($code, 'VOW-ERR')
                ? __('ui.vies_page.format_rejected', ['country' => $this->selectedCountry['name'] ?? $this->country_code])
                : __('ui.vies_page.service_unavailable');

            return;
        }

        $source = (string) ($data['source'] ?? 'vies_api');
        $name = $this->displayValue($data['name'] ?? null);
        $address = $this->displayValue($data['address'] ?? null);

        if ($source !== 'vies_api') {
            VatValidationLog::create([
                'country_code' => $this->country_code,
                'vat_number' => $this->vat_number,
                'is_valid' => (bool) ($data['valid'] ?? false),
                'name' => $name,
                'address' => $address,
                'request_identifier' => $data['request_identifier'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        $this->result = [
            'valid' => (bool) ($data['valid'] ?? false),
            'country_code' => $this->country_code,
            'prefix' => $this->selectedCountry['prefix'] ?? $this->country_code,
            'vat_number' => $this->vat_number,
            'name' => $name,
            'address' => $address,
            'request_identifier' => $this->displayValue($data['request_identifier'] ?? null),
            'source' => match ($source) {
                'vies_api' => 'live',
                'database_fallback' => 'fallback',
                default => 'recent',
            },
            'checked' => rescue(fn () => Carbon::parse($data['request_date'] ?? $data['last_checked_at'] ?? now())->toDateString(), now()->toDateString(), false),
        ];

        $this->dispatch('validation-complete',
            cc: $this->result['country_code'],
            prefix: $this->result['prefix'],
            vn: $this->result['vat_number'],
            valid: $this->result['valid'],
            name: $name,
        );
    }

    public function prefillExample(ViesValidationService $service): void
    {
        [$this->country_code, $this->vat_number] = self::EXAMPLE;

        $this->validateVat($service);
    }

    public function render()
    {
        return view('livewire.vies-validator-page', [
            'pageCountry' => $this->pageCountry,
        ]);
    }

    /**
     * Uppercases the number, drops separators and moves a recognised member-state prefix into the country field.
     */
    private function normalize(): void
    {
        $number = strtoupper((string) preg_replace('/[\s.\-\/]+/', '', $this->vat_number));
        $this->country_code = strtoupper(trim($this->country_code));

        if (strlen($number) > 4 && ctype_alpha(substr($number, 0, 2))) {
            $prefixed = collect($this->countries)->firstWhere('prefix', substr($number, 0, 2))
                ?? collect($this->countries)->firstWhere('iso', substr($number, 0, 2));

            if ($prefixed) {
                $this->country_code = $prefixed['iso'];
                $number = substr($number, 2);
            }
        }

        $this->vat_number = substr($number, 0, 32);
        unset($this->selectedCountry);
    }

    private function inputIsWellFormed(): bool
    {
        return in_array($this->country_code, array_column($this->countries, 'iso'), true)
            && preg_match(self::NUMBER_PATTERN, $this->vat_number) === 1;
    }

    private function displayValue(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return in_array($value, [null, '', '---', 'N/A'], true) ? null : $value;
    }
}
