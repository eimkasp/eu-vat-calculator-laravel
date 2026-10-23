<?php

namespace App\Support\DesignSystem;

final class DesignTokens
{
    public const SEMANTIC_GROUPS = [
        'Surfaces' => ['workspace', 'surface', 'surface-subtle', 'surface-muted', 'line', 'line-strong'],
        'Text' => ['ink', 'ink-muted', 'ink-quiet'],
        'Brand' => ['brand', 'brand-deep', 'brand-soft'],
        'Action' => ['action', 'action-deep', 'action-soft', 'button', 'button-hover'],
        'Status' => ['success', 'success-soft', 'warning', 'warning-soft', 'danger', 'danger-soft'],
    ];

    /** @var array<string, array<string, string>> */
    private array $scopes;

    public function __construct(private readonly string $css)
    {
        $this->scopes = [
            'theme' => $this->declarations('/^@theme\s*\{/m'),
            'inline' => $this->declarations('/^@theme inline\s*\{/m'),
            'light' => $this->declarations('/^:root(?:,\s*\[data-theme=\'light\'\])?\s*\{/m'),
            'dark' => $this->declarations('/^\[data-theme=\'dark\'\]\s*\{/m'),
        ];
    }

    public static function fromStylesheet(): self
    {
        static $instances = [];

        $path = resource_path('css/app.css');
        $key = $path.':'.filemtime($path);

        return $instances[$key] ??= new self(file_get_contents($path));
    }

    public function fingerprint(): string
    {
        return md5($this->css);
    }

    /**
     * Semantic colours that switch with the theme, in the order of SEMANTIC_GROUPS.
     *
     * @return array<string, array{variable: string, utility: string, group: string, light: Color, dark: Color}>
     */
    public function semanticColors(): array
    {
        $exposed = collect($this->scopes['inline'])
            ->filter(fn (string $value, string $name) => str_starts_with($name, 'color-') && preg_match('/^var\(--ui-([a-z-]+)\)$/', $value))
            ->mapWithKeys(fn (string $value, string $name) => [substr($name, 6) => 'ui-'.substr($name, 6)]);

        $colors = [];

        foreach (self::SEMANTIC_GROUPS as $group => $names) {
            foreach ($names as $name) {
                $variable = $exposed[$name] ?? null;

                if ($variable === null || ! isset($this->scopes['light'][$variable], $this->scopes['dark'][$variable])) {
                    continue;
                }

                $colors[$name] = [
                    'variable' => '--'.$variable,
                    'utility' => match (true) {
                        $group === 'Text' => 'text-'.$name,
                        str_starts_with($name, 'line') => 'border-'.$name,
                        default => 'bg-'.$name,
                    },
                    'group' => $group,
                    'light' => Color::parse($this->scopes['light'][$variable]),
                    'dark' => Color::parse($this->scopes['dark'][$variable]),
                ];
            }
        }

        return $colors;
    }

    /**
     * Colours from the @theme block that are identical in light and dark mode: gold and the code surfaces.
     *
     * @return array<string, array{variable: string, color: Color}>
     */
    public function fixedColors(): array
    {
        return $this->themeColors(fn (string $name) => ! str_starts_with($name, 'syntax-'));
    }

    /**
     * @return array<string, array{variable: string, color: Color}>
     */
    public function syntaxColors(): array
    {
        return collect($this->themeColors(fn (string $name) => str_starts_with($name, 'syntax-')))
            ->mapWithKeys(fn (array $token, string $name) => [substr($name, 7) => $token])
            ->all();
    }

    /**
     * The five-step VAT map ramp, stepped separately for each theme.
     *
     * @return array<int, array{variable: string, light: Color, dark: Color}>
     */
    public function mapRamp(): array
    {
        return collect($this->scopes['light'])
            ->filter(fn (string $value, string $name) => preg_match('/^map-\d$/', $name) && isset($this->scopes['dark'][$name]))
            ->map(fn (string $value, string $name) => [
                'variable' => '--'.$name,
                'light' => Color::parse($value),
                'dark' => Color::parse($this->scopes['dark'][$name]),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function radii(): array
    {
        return $this->themeValues('radius-');
    }

    /**
     * @return array<string, string>
     */
    public function shadows(): array
    {
        return $this->themeValues('shadow-');
    }

    /**
     * @return array<string, array<int, float>>
     */
    public function easings(): array
    {
        return collect($this->themeValues('ease-'))
            ->map(fn (string $value) => preg_match('/cubic-bezier\(([^)]+)\)/', $value, $match) ? array_map('floatval', explode(',', $match[1])) : null)
            ->filter()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function fonts(): array
    {
        return collect($this->themeValues('font-'))
            ->reject(fn (string $value, string $name) => str_starts_with($name, 'weight-'))
            ->map(fn (string $value) => array_map(fn (string $family) => trim($family, " '\""), explode(',', $value)))
            ->all();
    }

    /**
     * Transition durations in milliseconds: colour changes (pressable) and position changes (the segmented thumb).
     *
     * @return array<string, int>
     */
    public function durations(): array
    {
        $durations = [];

        if (preg_match('/@utility pressable \{[^}]*transition-duration:\s*(\d+)ms/', $this->css, $match)) {
            $durations['colour'] = (int) $match[1];
        }

        if (preg_match('/\.app-segmented-thumb \{[^}]*transition:[^;]*?(\d+)ms/', $this->css, $match)) {
            $durations['position'] = (int) $match[1];
        }

        return $durations;
    }

    /**
     * The app-container width and its gutters at the base, sm and lg breakpoints.
     *
     * @return array{max: string, gutters: array<int, string>}
     */
    public function container(): array
    {
        preg_match('/@utility app-container \{(.*?)\n\}/s', $this->css, $block);
        preg_match('/max-width:\s*([^;]+);/', $block[1] ?? '', $max);
        preg_match_all('/padding-inline:\s*([^;]+);/', $block[1] ?? '', $gutters);

        return ['max' => trim($max[1] ?? ''), 'gutters' => array_map('trim', $gutters[1])];
    }

    /**
     * Split a (possibly layered) box-shadow value into its layers.
     *
     * @return array<int, array{offsetX: string, offsetY: string, blur: string, spread: string, color: Color}>
     */
    public static function shadowLayers(string $value): array
    {
        $layers = [];

        foreach (preg_split('/,(?![^(]*\))/', $value) as $layer) {
            if (! preg_match('/^\s*(-?[\d.]+(?:px|rem)?)\s+(-?[\d.]+(?:px|rem)?)\s+(-?[\d.]+(?:px|rem)?)(?:\s+(-?[\d.]+(?:px|rem)?))?\s+(oklch\([^)]*\)|#[0-9a-f]{3,6})\s*$/i', $layer, $match)) {
                continue;
            }

            $layers[] = [
                'offsetX' => $match[1],
                'offsetY' => $match[2],
                'blur' => $match[3],
                'spread' => $match[4] !== '' ? $match[4] : '0',
                'color' => Color::parse($match[5]),
            ];
        }

        return $layers;
    }

    /**
     * @param  callable(string): bool  $accept
     * @return array<string, array{variable: string, color: Color}>
     */
    private function themeColors(callable $accept): array
    {
        return collect($this->themeValues('color-'))
            ->filter(fn (string $value, string $name) => $accept($name))
            ->mapWithKeys(fn (string $value, string $name) => [$name => ['variable' => '--color-'.$name, 'color' => Color::parse($value)]])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function themeValues(string $prefix): array
    {
        return collect($this->scopes['theme'])
            ->filter(fn (string $value, string $name) => str_starts_with($name, $prefix))
            ->mapWithKeys(fn (string $value, string $name) => [substr($name, strlen($prefix)) => $value])
            ->all();
    }

    /**
     * Custom property declarations at the top level of the first block whose opening matches the pattern.
     *
     * @return array<string, string>
     */
    private function declarations(string $opening): array
    {
        if (! preg_match($opening, $this->css, $match, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $offset = $match[0][1] + strlen($match[0][0]);
        $depth = 1;
        $body = '';

        for ($i = $offset, $length = strlen($this->css); $i < $length && $depth > 0; $i++) {
            $char = $this->css[$i];
            $depth += $char === '{' ? 1 : ($char === '}' ? -1 : 0);

            if ($depth === 1 && $char !== '}') {
                $body .= $char;
            }
        }

        $body = preg_replace('#/\*.*?\*/#s', '', $body);
        preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $body, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $declaration) => [$declaration[1] => trim($declaration[2])])->all();
    }
}
