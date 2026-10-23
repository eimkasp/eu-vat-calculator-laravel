<?php

namespace App\Support\DesignSystem;

final class TokenDocument
{
    public const EXTENSION = 'io.businesspress.vat';

    public const MEDIA_TYPE = 'application/design-tokens+json';

    public function __construct(
        private readonly DesignTokens $tokens,
        private readonly DesignGuide $guide,
    ) {}

    public static function current(): self
    {
        return new self(DesignTokens::fromStylesheet(), DesignGuide::fromRepository());
    }

    /**
     * The tokens in the Design Tokens Community Group format (2025.10).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $uses = $this->guide->tokenRows('Color roles');

        return [
            '$description' => 'EU VAT Info design tokens, generated from resources/css/app.css. Light values are the defaults; dark values are under $extensions.'.self::EXTENSION.'.dark. Documentation: '.url('/styleguide'),
            'color' => [
                '$type' => 'color',
                'semantic' => collect($this->tokens->semanticColors())->map(fn (array $token, string $name) => $this->colorToken(
                    $token['light'],
                    $uses[$name]['Use'] ?? null,
                    ['cssVariable' => $token['variable'], 'utility' => $token['utility'], 'group' => $token['group'], 'dark' => $token['dark']->toDtcg()],
                ))->all(),
                'fixed' => collect($this->tokens->fixedColors())->map(fn (array $token, string $name) => $this->colorToken(
                    $token['color'],
                    $uses[$name]['Use'] ?? null,
                    ['cssVariable' => $token['variable'], 'utility' => 'bg-'.$name],
                ))->all(),
                'syntax' => collect($this->tokens->syntaxColors())->map(fn (array $token, string $name) => $this->colorToken(
                    $token['color'],
                    $uses['color-syntax-'.$name]['Use'] ?? null,
                    ['cssVariable' => $token['variable'], 'utility' => 'text-syntax-'.$name],
                ))->all(),
                'map' => collect($this->tokens->mapRamp())->mapWithKeys(fn (array $step, int $index) => ['step-'.$index => $this->colorToken(
                    $step['light'],
                    'Step '.($index + 1).' of the five-step VAT map ramp, stepped separately for dark mode.',
                    ['cssVariable' => $step['variable'], 'dark' => $step['dark']->toDtcg()],
                )])->all(),
            ],
            'font' => [
                '$type' => 'fontFamily',
                ...collect($this->tokens->fonts())->map(fn (array $families) => ['$value' => $families])->all(),
            ],
            'typography' => [
                '$type' => 'typography',
                ...collect($this->guide->typeScale())->map(fn (array $style) => $this->typographyToken($style))->all(),
            ],
            'radius' => [
                '$type' => 'dimension',
                ...collect($this->tokens->radii())->map(fn (string $value, string $name) => [
                    '$value' => self::dimension($value),
                    '$extensions' => [self::EXTENSION => ['cssVariable' => '--radius-'.$name, 'utility' => 'rounded-'.$name]],
                ])->all(),
            ],
            'shadow' => [
                '$type' => 'shadow',
                ...collect($this->tokens->shadows())->map(fn (string $value, string $name) => [
                    '$value' => array_map(fn (array $layer) => [
                        'color' => $layer['color']->toDtcg(),
                        'offsetX' => self::dimension($layer['offsetX']),
                        'offsetY' => self::dimension($layer['offsetY']),
                        'blur' => self::dimension($layer['blur']),
                        'spread' => self::dimension($layer['spread']),
                    ], DesignTokens::shadowLayers($value)),
                    '$extensions' => [self::EXTENSION => ['cssVariable' => '--shadow-'.$name, 'utility' => 'shadow-'.$name]],
                ])->all(),
            ],
            'easing' => [
                '$type' => 'cubicBezier',
                ...collect($this->tokens->easings())->map(fn (array $curve, string $name) => [
                    '$value' => $curve,
                    '$extensions' => [self::EXTENSION => ['cssVariable' => '--ease-'.$name, 'utility' => 'ease-'.$name]],
                ])->all(),
            ],
            'duration' => [
                '$type' => 'duration',
                ...collect($this->tokens->durations())->map(fn (int $milliseconds) => ['$value' => ['value' => $milliseconds, 'unit' => 'ms']])->all(),
            ],
            'size' => [
                '$type' => 'dimension',
                'container' => ['$value' => self::dimension($this->tokens->container()['max']), '$description' => 'Maximum width of app-container.'],
                'gutter' => collect(['base', 'sm', 'lg'])
                    ->combine(array_pad($this->tokens->container()['gutters'], 3, '0'))
                    ->map(fn (string $value, string $breakpoint) => ['$value' => self::dimension($value), '$description' => "Inline padding of app-container from the {$breakpoint} breakpoint."])
                    ->all(),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @return array{value: float, unit: string}
     */
    public static function dimension(string $value): array
    {
        preg_match('/^(-?[\d.]+)(px|rem)?$/', trim($value), $match);

        return ['value' => (float) ($match[1] ?? 0), 'unit' => ($match[2] ?? '') ?: 'px'];
    }

    /**
     * @param  array<string, mixed>  $extension
     * @return array<string, mixed>
     */
    private function colorToken(Color $color, ?string $description, array $extension): array
    {
        return array_filter([
            '$value' => $color->toDtcg(),
            '$description' => $description !== null ? strip_tags(str_replace('`', '', $description)) : null,
            '$extensions' => [self::EXTENSION => $extension],
        ]);
    }

    /**
     * @param  array<string, string>  $style
     * @return array<string, mixed>
     */
    private function typographyToken(array $style): array
    {
        $size = self::dimension($style['size'] ?? '1rem');
        $tracking = (float) rtrim($style['letterSpacing'] ?? '0', 'em');

        return array_filter([
            '$value' => [
                'fontFamily' => '{font.'.($style['family'] ?? 'sans').'}',
                'fontSize' => $size,
                'fontWeight' => (int) ($style['weight'] ?? 400),
                'letterSpacing' => ['value' => round($tracking * $size['value'], 4), 'unit' => $size['unit']],
                'lineHeight' => (float) ($style['lineHeight'] ?? 1.5),
            ],
            '$extensions' => isset($style['transform']) ? [self::EXTENSION => ['textTransform' => $style['transform']]] : null,
        ]);
    }
}
