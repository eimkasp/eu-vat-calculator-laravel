<?php

use App\Services\BlogPostRepository;

it('renders the blog index from filesystem markdown posts', function () {
    $this->get('/blog')
        ->assertOk()
        ->assertSee('Research-backed VAT changes and guides')
        ->assertSee('Upcoming VAT changes in 2026 and 2027');
});

it('parses markdown front matter without external yaml dependencies', function () {
    $post = app(BlogPostRepository::class)->find('upcoming-vat-changes-2026-2027');

    expect($post['title'])->toBe('Upcoming VAT changes in 2026 and 2027')
        ->and($post['tags'])->toContain('ViDA')
        ->and($post['sources'])->toHaveCount(8)
        ->and($post['sources'][0])->toMatchArray([
            'title' => 'European Commission: VAT in the Digital Age',
            'url' => 'https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en',
        ]);
});

it('renders the VAT changes article from markdown', function () {
    $this->get('/blog/upcoming-vat-changes-2026-2027')
        ->assertOk()
        ->assertSee('Upcoming VAT changes in 2026 and 2027')
        ->assertSee('Ireland')
        ->assertSee('1 July 2026')
        ->assertSee('VAT change alerts');
});

it('renders the VAT changes article on locale-prefixed routes', function () {
    $this->get('/de/blog/upcoming-vat-changes-2026-2027')
        ->assertOk()
        ->assertSee('Upcoming VAT changes in 2026 and 2027');
});

it('returns not found for missing blog posts', function () {
    $this->get('/blog/missing-vat-article')
        ->assertNotFound();
});
