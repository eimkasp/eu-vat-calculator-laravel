<?php

namespace App\Livewire;

use App\Support\DesignSystem\Color;
use App\Support\DesignSystem\ComponentCatalog;
use App\Support\DesignSystem\DesignGuide;
use App\Support\DesignSystem\DesignTokens;
use Livewire\Component;

class Styleguide extends Component
{
    public const STORYBOOK_VERSION = '10.6.0';

    public const BUSINESSPRESS_URL = 'https://businesspress.io/?utm_source=eu-vat-info&utm_medium=referral&utm_campaign=design-system';

    public const SECTIONS = [
        'Foundations' => [
            'principles' => 'Principles',
            'colour' => 'Colour',
            'typography' => 'Typography',
            'shape' => 'Shape',
            'elevation' => 'Elevation',
            'motion' => 'Motion',
            'layout' => 'Layout',
        ],
        'Components' => [
            'buttons' => 'Buttons',
            'fields' => 'Form fields',
            'selection' => 'Selection controls',
            'surfaces' => 'Surfaces',
            'content' => 'Text and code',
            'data' => 'Data display',
            'navigation' => 'Navigation',
            'overlays' => 'Menus and toasts',
            'icons' => 'Icons',
            'identity' => 'Flags and logo',
        ],
        'Patterns' => [
            'skeletons' => 'Page skeletons',
            'results' => 'Results',
            'country' => 'Country pages',
            'tiles' => 'Tiles and rows',
            'states' => 'States',
            'calls-to-action' => 'Calls to action',
        ],
        'Resources' => [
            'storybook' => 'Storybook',
            'agents' => 'For AI agents',
            'rules' => 'Rules and checks',
        ],
    ];

    private const CONTRAST_PAIRS = [
        ['Body text', 'ink', 'surface'],
        ['Supporting text', 'ink-muted', 'surface'],
        ['Meta and placeholders', 'ink-quiet', 'surface'],
        ['Links and selection', 'action', 'surface'],
        ['Selected chip', 'action-deep', 'action-soft'],
        ['Success', 'success', 'success-soft'],
        ['Warning', 'warning', 'warning-soft'],
        ['Danger', 'danger', 'danger-soft'],
        ['Button label', 'white', 'button'],
        ['Text on navy', 'white', 'brand'],
        ['Gold on navy', 'gold', 'brand'],
    ];

    public function render()
    {
        $tokens = DesignTokens::fromStylesheet();
        $guide = DesignGuide::fromRepository();
        $colors = $tokens->semanticColors();
        $icons = self::iconNames();

        return view('livewire.styleguide', [
            'tokens' => $tokens,
            'guide' => $guide,
            'colors' => $colors,
            'uses' => $guide->tokenRows('Color roles'),
            'contrast' => $this->contrast($colors, $tokens->fixedColors()),
            'icons' => $icons,
            'flagCount' => count(glob(public_path('images/flags/*.svg')) ?: []),
            'stats' => [
                ['value' => count($colors) + count($tokens->fixedColors()) + count($tokens->syntaxColors()) + count($tokens->mapRamp()), 'label' => 'Colour tokens'],
                ['value' => count($tokens->radii()) + count($tokens->shadows()) + count($tokens->easings()), 'label' => 'Shape, depth and motion tokens'],
                ['value' => count(self::componentClasses()), 'label' => 'Component classes'],
                ['value' => count($icons), 'label' => 'Icons'],
                ['value' => count(ComponentCatalog::EXAMPLES), 'label' => 'Live examples'],
                ['value' => count(glob(resource_path('views/components/{,*/}*.blade.php'), GLOB_BRACE) ?: []), 'label' => 'Blade components'],
            ],
        ]);
    }

    /**
     * Icon names drawn by <x-ui.icon>, in the order of the component.
     *
     * @return array<int, string>
     */
    public static function iconNames(): array
    {
        preg_match_all("/^\s+'([a-z0-9-]+)' => '</m", file_get_contents(resource_path('views/components/ui/icon.blade.php')), $matches);

        return $matches[1];
    }

    /**
     * The app-* and hero-* component classes defined in the stylesheet.
     *
     * @return array<int, string>
     */
    public static function componentClasses(): array
    {
        preg_match_all('/^\s*\.((?:app|hero)-[a-z-]+)\b/m', file_get_contents(resource_path('css/app.css')), $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  array<string, array{light: Color, dark: Color}>  $colors
     * @param  array<string, array{color: Color}>  $fixed
     * @return array<int, array{label: string, foreground: string, background: string, light: float, dark: float}>
     */
    private function contrast(array $colors, array $fixed): array
    {
        $white = Color::parse('#ffffff');
        $resolve = fn (string $name, string $theme) => match (true) {
            $name === 'white' => $white,
            isset($fixed[$name]) => $fixed[$name]['color'],
            default => $colors[$name][$theme],
        };

        return collect(self::CONTRAST_PAIRS)
            ->filter(fn (array $pair) => collect([$pair[1], $pair[2]])->every(fn (string $name) => $name === 'white' || isset($colors[$name]) || isset($fixed[$name])))
            ->map(fn (array $pair) => [
                'label' => $pair[0],
                'foreground' => $pair[1],
                'background' => $pair[2],
                'light' => round($resolve($pair[1], 'light')->contrast($resolve($pair[2], 'light')), 2),
                'dark' => round($resolve($pair[1], 'dark')->contrast($resolve($pair[2], 'dark')), 2),
            ])
            ->values()
            ->all();
    }
}
