<?php

use App\Models\Country;

it('serves pages without Blade indentation and keeps them working', function () {
    Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'reduced_rate' => 7, 'is_eu_member' => true, 'vies_available' => true]);

    $html = $this->get('/vat-calculator/germany')->assertOk()->assertSee('Germany VAT calculator')->getContent();
    $body = preg_replace('#<(pre|textarea|script|style)\b.*?</\1>#is', '<kept/>', $html);

    expect(preg_match('/>[ \t\r\n]{2,}|[ \t\r\n]{2,}</', $body))->toBe(0)
        ->and($html)->toContain('wire:snapshot');
});

it('leaves Markdown, JSON and other responses alone', function () {
    $this->get('/api/v1/countries')->assertOk()->assertHeader('Content-Type', 'application/json');
    $this->get('/styleguide.md')->assertOk();
});
