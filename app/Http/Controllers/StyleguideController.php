<?php

namespace App\Http\Controllers;

use App\Support\DesignSystem\DesignGuide;
use App\Support\DesignSystem\TokenDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;

/**
 * Machine-readable companions to /styleguide and /brand: the design tokens in the Design Tokens Community Group
 * format, DESIGN.md as Markdown and the mark as SVG, all generated from the files the site is built from.
 */
class StyleguideController extends Controller
{
    public function tokens(Request $request): Response
    {
        return $this->respond($request, TokenDocument::current()->toJson(), TokenDocument::MEDIA_TYPE.'; charset=UTF-8', [
            'Content-Disposition' => 'inline; filename="eu-vat-info.tokens.json"',
        ], [resource_path('css/app.css'), base_path('DESIGN.md')]);
    }

    public function markdown(Request $request): Response
    {
        return $this->respond($request, DesignGuide::fromRepository()->markdown(), 'text/markdown; charset=UTF-8', [], [base_path('DESIGN.md')]);
    }

    /**
     * The mark as a standalone SVG for the brand page, drawn by the same component the site uses.
     */
    public function mark(Request $request): Response
    {
        $svg = Blade::render('<x-ui.logo width="512" height="512" class="" />');
        $svg = '<?xml version="1.0" encoding="UTF-8"?>'."\n".preg_replace(['/\s(?:class|aria-hidden|focusable)="[^"]*"/', '/\s+/'], ['', ' '], trim($svg))."\n";

        return $this->respond($request, $svg, 'image/svg+xml', [
            'Content-Disposition' => 'inline; filename="eu-vat-info-mark.svg"',
        ], [resource_path('views/components/ui/logo.blade.php')]);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<int, string>  $sources
     */
    private function respond(Request $request, string $body, string $type, array $headers, array $sources): Response
    {
        $etag = '"'.md5($body).'"';
        $modified = gmdate('D, d M Y H:i:s', max(array_map('filemtime', $sources))).' GMT';

        $response = response($body, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => $etag,
            'Last-Modified' => $modified,
            'Access-Control-Allow-Origin' => '*',
            'X-Robots-Tag' => 'noindex',
            ...$headers,
        ]);

        return $request->headers->get('If-None-Match') === $etag ? $response->setNotModified() : $response;
    }
}
