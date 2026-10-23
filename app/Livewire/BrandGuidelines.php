<?php

namespace App\Livewire;

use App\Support\DesignSystem\DesignGuide;
use App\Support\DesignSystem\TokenDocument;
use Livewire\Component;

/**
 * /brand: the mark, colours, type and voice of EU VAT Info for anyone who writes about, links to or builds with it.
 * Colours come from the Design Tokens, so the page never disagrees with the stylesheet.
 */
class BrandGuidelines extends Component
{
    public const COLOURS = ['ink', 'brand', 'action', 'button', 'action-soft', 'ink-muted', 'workspace', 'surface'];

    public function render()
    {
        $tokens = TokenDocument::current()->toArray()['color'];

        return view('livewire.brand-guidelines', [
            'version' => DesignGuide::fromRepository()->version(),
            'colours' => collect(self::COLOURS)->map(function (string $name) use ($tokens) {
                $token = $tokens['semantic'][$name] ?? $tokens['fixed'][$name];
                $extension = $token['$extensions']['io.businesspress.vat'] ?? [];

                return [
                    'name' => $name,
                    'hex' => strtoupper($token['$value']['hex']),
                    'dark' => isset($extension['dark']['hex']) ? strtoupper($extension['dark']['hex']) : null,
                    'description' => $token['$description'] ?? null,
                ];
            })->all(),
        ]);
    }
}
