<?php

namespace App\View\Components;

use App\Support\Seo\SeoPolicy;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteBreadcrumbs extends Component
{
    public function __construct(
        public array $items,
        public string $variant = 'light',
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.breadcrumbs', [
            'schema' => JsonLd::hasBreadcrumbList() ? null : $this->schema(),
        ]);
    }

    /**
     * The visible trail as a BreadcrumbList, for pages that do not describe their breadcrumbs themselves.
     *
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        $seo = app(SeoPolicy::class);
        $elements = [];

        foreach ([__('ui.breadcrumbs.home') => locale_path('/')] + $this->items as $label => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'name' => (string) $label,
                'item' => $url ? $seo->canonicalizeLocalUrl(url($url)) : $seo->canonicalPageUrl(url()->current()),
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }
}
