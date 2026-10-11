<?php

use App\Models\Country;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    Country::factory()->create([
        'name' => 'Austria', 'slug' => 'austria', 'iso_code' => 'AT', 'standard_rate' => 20, 'reduced_rate' => '10 / 13',
        'super_reduced_rate' => 4.9, 'parking_rate' => 13, 'currency_code' => 'EUR', 'is_eu_member' => true, 'vies_available' => true,
    ]);
    Country::factory()->create([
        'name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25, 'reduced_rate' => '12 / 15',
        'super_reduced_rate' => null, 'parking_rate' => null, 'currency_code' => 'NOK', 'is_eu_member' => false, 'vies_available' => false,
    ]);
});

it('gives every rate type, the VAT number format and the sources in llms.txt', function () {
    $text = $this->get('/llms.txt')->assertOk()->getContent();

    expect($text)
        ->toContain('20% standard; reduced 10%, 13%; super-reduced 4.9%; parking 13%; EUR; VAT numbers ATU99999999')
        ->toContain('25% standard; reduced 12%, 15%; NOK')
        ->toContain('## Sources')
        ->toContain('https://europa.eu/youreurope/business/finance-and-tax/vat/vat-rules-rates/index_en.htm')
        ->toContain('updated every week')
        ->not->toContain('refreshed daily');
});

it('lists the VAT number format of each member state in llms-full.txt', function () {
    $this->get('/llms-full.txt')
        ->assertOk()
        ->assertSee('| Country | ISO | VAT prefix | VAT number format | Currency |', false)
        ->assertSee('| Austria | AT | AT | ATU99999999 | EUR | 20% | 10%, 13% | 4.9% | 13% |', false)
        ->assertSee('| Norway | NO | NOK | 25% | 12%, 15% | - | - |', false)
        ->assertSee('## Sources');
});

it('returns the VAT number format with each country in the API and the MCP server', function () {
    $this->getJson('/api/v1/countries/austria')
        ->assertOk()
        ->assertJsonPath('data.vat_number.prefix', 'AT')
        ->assertJsonPath('data.vat_number.format', 'ATU99999999')
        ->assertJsonPath('data.vat_number.pattern', '^ATU\d{8}$');

    $this->getJson('/api/v1/countries/norway')->assertOk()->assertJsonPath('data.vat_number', null);

    $schema = $this->getJson('/api/v1/openapi.json')->assertOk()->json('components.schemas.Country.properties');
    expect($schema)->toHaveKeys(['vat_number', 'rates'])
        ->and($schema['rates']['properties'])->toHaveKey('reduced_rates');

    $result = $this->call('POST', '/api/mcp', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'], json_encode([
        'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get_country_vat_rate', 'arguments' => ['country' => 'AT']],
    ]))->assertOk()->json('result.structuredContent');

    expect($result['vat_number']['format'])->toBe('ATU99999999');
});

it('serves pages as clean Markdown for agents', function () {
    $markdown = $this->get('/vat-calculator/austria', ['Accept' => 'text/markdown'])
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->getContent();

    expect($markdown)
        ->toContain('| Amount | Adding 20% VAT | Removing 20% VAT |')
        ->toContain('(https://vat.businesspress.io/vat-calculation/austria/100/20/exclude)')
        ->toContain('### What is the VAT rate in Austria?')
        ->not->toContain(__('ui.calculator.no_countries_found'))
        ->not->toContain(__('ui.calculator.invalid_amount'))
        ->not->toContain('![](')
        ->not->toMatch('/^ {4}\S/m')
        ->and($this->get('/vat-number-validator', ['Accept' => 'text/markdown'])->getContent())->not->toMatch('/^```\n```$/m');
});

it('dates the country calculator page in its structured data', function () {
    $austria = Country::query()->where('iso_code', 'AT')->firstOrFail();

    $this->get('/vat-calculator/austria')
        ->assertOk()
        ->assertSee('"dateModified":"'.$austria->updated_at->toIso8601String().'"', false);
});
