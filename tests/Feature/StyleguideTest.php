<?php

use App\Livewire\Styleguide;
use App\Support\DesignSystem\CodeHighlighter;
use App\Support\DesignSystem\Color;
use App\Support\DesignSystem\DesignGuide;
use App\Support\DesignSystem\DesignTokens;
use App\Support\DesignSystem\TokenDocument;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
});

/**
 * The DESIGN.md front matter entries of a block such as `colors:` or `rounded:`.
 *
 * @return array<string, string>
 */
function designFrontmatter(string $block): array
{
    preg_match('/^---\n(.*?)\n---/s', file_get_contents(base_path('DESIGN.md')), $frontmatter);
    preg_match('/^'.preg_quote($block, '/').':\n((?:  .+\n?)+)/m', $frontmatter[1] ?? '', $section);
    preg_match_all('/^  ([a-z-]+): "([^"]+)"$/m', $section[1] ?? '', $entries, PREG_SET_ORDER);

    return collect($entries)->mapWithKeys(fn (array $entry) => [$entry[1] => $entry[2]])->all();
}

/**
 * Validate a Design Tokens Community Group (2025.10) document and return the token paths it defines.
 *
 * @param  array<string, mixed>  $group
 * @param  array<int, string>  $aliases
 * @return array<int, string>
 */
function assertDtcgGroup(array $group, ?string $type = null, string $path = '', array &$aliases = []): array
{
    $type = $group['$type'] ?? $type;

    if (array_key_exists('$value', $group)) {
        expect($type)->toBeIn(['color', 'dimension', 'fontFamily', 'typography', 'shadow', 'cubicBezier', 'duration'], "{$path} has no valid \$type");

        $dimension = fn (mixed $value) => expect($value)->toBeArray()->toHaveKeys(['value', 'unit'])
            ->and($value['value'])->toBeFloat()
            ->and($value['unit'])->toBeIn(['px', 'rem']);
        $value = $group['$value'];

        match ($type) {
            'color' => expect($value)->toHaveKeys(['colorSpace', 'components', 'alpha', 'hex'])
                ->and($value['colorSpace'])->toBeIn(['oklch', 'srgb'])
                ->and($value['components'])->toHaveCount(3)
                ->and($value['alpha'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(1)
                ->and($value['hex'])->toMatch('/^#[0-9a-f]{6}$/'),
            'dimension' => $dimension($value),
            'fontFamily' => expect($value)->toBeArray()->not->toBeEmpty()->each->toBeString(),
            'typography' => (function () use ($value, $dimension, &$aliases) {
                expect($value)->toHaveKeys(['fontFamily', 'fontSize', 'fontWeight', 'letterSpacing', 'lineHeight'])
                    ->and($value['fontWeight'])->toBeInt()->toBeGreaterThanOrEqual(100)->toBeLessThanOrEqual(900)
                    ->and($value['lineHeight'])->toBeFloat()->toBeGreaterThan(0);
                $dimension($value['fontSize']);
                $dimension($value['letterSpacing']);
                $aliases[] = $value['fontFamily'];
            })(),
            'shadow' => expect($value)->toBeArray()->not->toBeEmpty()
                ->each(fn ($layer) => $layer->toHaveKeys(['color', 'offsetX', 'offsetY', 'blur', 'spread'])),
            'cubicBezier' => expect($value)->toHaveCount(4)
                ->and($value[0])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(1)
                ->and($value[2])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(1),
            'duration' => expect($value)->toHaveKeys(['value', 'unit'])->and($value['unit'])->toBeIn(['ms', 's']),
        };

        if (isset($group['$extensions'])) {
            expect(array_keys($group['$extensions']))->toBe([TokenDocument::EXTENSION]);
        }

        return [$path];
    }

    return collect($group)
        ->reject(fn (mixed $value, string $key) => str_starts_with($key, '$'))
        ->flatMap(function (mixed $child, string $key) use ($type, $path, &$aliases) {
            expect($child)->toBeArray("{$path}.{$key} is not a group or token");

            return assertDtcgGroup($child, $type, ltrim("{$path}.{$key}", '.'), $aliases);
        })
        ->all();
}

it('renders every section, icon and the BusinessPress offer', function () {
    $response = $this->get('/styleguide')->assertOk();

    $response
        ->assertSee('The EU VAT Info design system')
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/styleguide">', false)
        ->assertSee('href="'.url('/styleguide/design.tokens.json').'"', false)
        ->assertSee('href="'.url('/styleguide.md').'"', false)
        ->assertSee('href="'.e(Styleguide::BUSINESSPRESS_URL).'"', false)
        ->assertSee('Get a design system like this for your business')
        ->assertSee('"@type":"TechArticle"', false)
        ->assertDontSee('hreflang=', false);

    foreach (collect(Styleguide::SECTIONS)->flatMap(fn (array $sections) => array_keys($sections)) as $id) {
        $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
    }

    foreach (Styleguide::iconNames() as $icon) {
        $response->assertSee("matches('{$icon}')", false);
    }

    expect(Styleguide::iconNames())->toHaveCount(count(array_unique(Styleguide::iconNames())))
        ->and(Styleguide::componentClasses())->toContain('app-surface', 'app-button-primary', 'hero-canvas');
});

it('keeps the English reference and its English canonical in other languages', function () {
    config()->set('seo.indexable_locales', ['en', 'de']);

    $this->get('/de/styleguide')
        ->assertOk()
        ->assertSee(__('ui.styleguide.english_only', [], 'de'))
        ->assertSee('The EU VAT Info design system')
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/styleguide">', false)
        ->assertDontSee('hreflang=', false);
});

it('shows every example file as a specimen', function () {
    $view = file_get_contents(resource_path('views/livewire/styleguide.blade.php'));
    $examples = collect(glob(resource_path('views/styleguide/examples/*.blade.php')))
        ->map(fn (string $path) => basename($path, '.blade.php'));

    expect($examples)->not->toBeEmpty();

    foreach ($examples as $example) {
        expect($view)->toContain('example="'.$example.'"');
    }

    preg_match_all('/example="([a-z0-9-]+)"/', $view, $used);

    expect(array_diff($used[1], $examples->all()))->toBe([]);
});

it('keeps every text pair it lists at WCAG AA in both themes', function () {
    Livewire::test(Styleguide::class)->assertViewHas('contrast', function (array $pairs) {
        expect($pairs)->toHaveCount(11);

        foreach ($pairs as $pair) {
            expect($pair['light'])->toBeGreaterThanOrEqual(4.5, "{$pair['label']} fails AA in light mode")
                ->and($pair['dark'])->toBeGreaterThanOrEqual(4.5, "{$pair['label']} fails AA in dark mode");
        }

        return true;
    });
});

it('exports valid Design Tokens Community Group JSON', function () {
    $response = $this->get('/styleguide/design.tokens.json')
        ->assertOk()
        ->assertHeader('Content-Type', TokenDocument::MEDIA_TYPE.'; charset=UTF-8')
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeader('X-Robots-Tag', 'noindex');

    $document = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $aliases = [];
    $paths = assertDtcgGroup($document, aliases: $aliases);

    expect($paths)->toContain('color.semantic.surface', 'color.fixed.code', 'font.sans', 'typography.display', 'radius.control', 'shadow.card', 'easing.out-quint', 'duration.colour', 'size.container');

    foreach (array_unique($aliases) as $alias) {
        expect($alias)->toMatch('/^\{[a-z0-9.-]+\}$/')
            ->and($paths)->toContain(trim($alias, '{}'));
    }
});

it('generates the tokens from the stylesheet and agrees with DESIGN.md', function () {
    $tokens = DesignTokens::fromStylesheet();
    $document = TokenDocument::current()->toArray();
    $semantic = $document['color']['semantic'];

    expect(array_keys($semantic))->toBe(collect(DesignTokens::SEMANTIC_GROUPS)->flatten()->all());

    foreach ($tokens->semanticColors() as $name => $token) {
        expect($semantic[$name]['$value']['hex'])->toBe($token['light']->hex())
            ->and($semantic[$name]['$extensions'][TokenDocument::EXTENSION])->toMatchArray([
                'cssVariable' => '--ui-'.$name,
                'dark' => $token['dark']->toDtcg(),
            ]);
    }

    foreach (designFrontmatter('colors') as $name => $hex) {
        $token = $semantic[$name] ?? $document['color']['fixed'][$name] ?? null;

        expect($token)->not->toBeNull("DESIGN.md lists {$name}, which the stylesheet does not define")
            ->and($token['$value']['hex'])->toBe(strtolower($hex), "DESIGN.md and app.css disagree on {$name}");
    }

    foreach (designFrontmatter('rounded') as $name => $size) {
        $radius = $document['radius'][$name]['$value'];

        expect($radius['value'] * ($radius['unit'] === 'rem' ? 16 : 1))->toBe((float) rtrim($size, 'px'));
    }

    $guide = DesignGuide::fromRepository();

    expect($document['typography'])->toHaveKeys(array_keys($guide->typeScale()))
        ->and($document['typography']['display']['$value'])->toMatchArray([
            'fontFamily' => '{font.display}',
            'fontSize' => ['value' => 3.0, 'unit' => 'rem'],
            'fontWeight' => 600,
            'lineHeight' => 1.04,
        ])
        ->and($document['font']['sans']['$value'][0])->toBe('IBM Plex Sans Variable')
        ->and($document['easing']['out-quint']['$value'])->toBe([0.22, 1.0, 0.36, 1.0])
        ->and($document['duration'])->toBe([
            '$type' => 'duration',
            'colour' => ['$value' => ['value' => 120, 'unit' => 'ms']],
            'position' => ['$value' => ['value' => 200, 'unit' => 'ms']],
        ])
        ->and($document['size']['container']['$value'])->toBe(['value' => 80.0, 'unit' => 'rem'])
        ->and($document['color']['map'])->toHaveCount(5);
});

it('answers conditional requests for the exports', function (string $url) {
    $first = $this->get($url)->assertOk();

    expect($first->headers->get('ETag'))->not->toBeEmpty()
        ->and($first->headers->get('Cache-Control'))->toContain('max-age=3600', 'public');

    $this->get($url, ['If-None-Match' => $first->headers->get('ETag')])->assertStatus(304);
})->with(['/styleguide/design.tokens.json', '/styleguide.md']);

it('serves DESIGN.md as Markdown', function () {
    $response = $this->get('/styleguide.md')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

    expect($response->getContent())->toBe(file_get_contents(base_path('DESIGN.md')));
});

it('is linked from the footer, the site map, quick search, llms.txt and the XML sitemap', function () {
    config()->set('seo.indexable_locales', ['en', 'de']);

    $this->get('/sitemap')
        ->assertOk()
        ->assertSee(__('ui.styleguide.nav_desc'))
        ->assertSee('href="/styleguide"', false);

    $this->getJson('/search-index.json?locale=en')->assertJsonFragment(['url' => '/styleguide']);

    $this->get('/llms.txt')
        ->assertSee('/styleguide/design.tokens.json')
        ->assertSee('/styleguide.md');

    $this->get('/sitemaps/core.xml')
        ->assertOk()
        ->assertSee('<loc>https://vat.businesspress.io/styleguide</loc>', false)
        ->assertDontSee('https://vat.businesspress.io/de/styleguide', false)
        ->assertSee('<loc>https://vat.businesspress.io/mcp-server</loc>', false)
        ->assertSee('<loc>https://vat.businesspress.io/de/mcp-server</loc>', false);
});

it('converts OKLCH to sRGB and measures contrast like WCAG', function () {
    expect(Color::parse('oklch(0.627955 0.257683 29.2339)')->hex())->toBe('#ff0000')
        ->and(Color::parse('oklch(0.519752 0.176858 142.4953)')->hex())->toBe('#008000')
        ->and(Color::parse('oklch(0.452014 0.313214 264.052)')->hex())->toBe('#0000ff')
        ->and(Color::parse('#FFF')->hex())->toBe('#ffffff')
        ->and(Color::parse('#ffffff')->contrast(Color::parse('#000000')))->toEqualWithDelta(21.0, 0.001)
        ->and(Color::parse('oklch(50% 0.1 250 / 40%)')->toDtcg())->toBe([
            'colorSpace' => 'oklch',
            'components' => [0.5, 0.1, 250.0],
            'alpha' => 0.4,
            'hex' => '#32669a',
        ])
        ->and(TokenDocument::dimension('0.25rem'))->toBe(['value' => 0.25, 'unit' => 'rem'])
        ->and(TokenDocument::dimension('0'))->toBe(['value' => 0.0, 'unit' => 'px']);

    expect(fn () => Color::parse('rgb(0 0 0)'))->toThrow(InvalidArgumentException::class);
});

it('escapes and highlights Blade source for the code panels', function () {
    $html = CodeHighlighter::blade('<x-ui.icon name="copy" /> {{ $total }} {{-- note --}} <script>', [
        'comment' => 'c',
        'attribute' => 'a',
        'blade' => 'b',
        'tag' => 't',
        'string' => 's',
    ]);

    expect($html)
        ->toContain('<span class="t">&lt;x-ui.icon</span>')
        ->toContain('<span class="a">name</span>=<span class="s">&quot;copy&quot;</span>')
        ->toContain('<span class="b">{{ $total }}</span>')
        ->toContain('<span class="c">{{-- note --}}</span>')
        ->toContain('<span class="t">&lt;script</span>')
        ->not->toContain('<script');
});
