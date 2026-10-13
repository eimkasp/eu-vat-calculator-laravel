<?php

it('uses the current PDFCheck domain in public partner links', function () {
    $home = $this->get('/')
        ->assertOk()
        ->assertSee('href="https://pdfcheck.online/"', false)
        ->assertDontSee('pdf.businesspress.io', false);

    expect(substr_count($home->getContent(), 'href="https://pdfcheck.online/"'))->toBe(1);

    $sitemap = $this->get('/sitemap')
        ->assertOk()
        ->assertSee('href="https://pdfcheck.online/"', false)
        ->assertDontSee('pdf.businesspress.io', false);

    expect(substr_count($sitemap->getContent(), 'href="https://pdfcheck.online/"'))->toBe(2);
});
