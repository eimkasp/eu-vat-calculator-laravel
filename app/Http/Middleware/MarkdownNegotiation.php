<?php

namespace App\Http\Middleware;

use Closure;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Request;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\HttpFoundation\Response;

class MarkdownNegotiation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->wantsMarkdown($request)) {
            return $response;
        }

        if (! $response->headers->has('Content-Type')
            || ! str_contains($response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $html = $response->getContent();

        $markdown = $this->convertToMarkdown($html);

        $response->setContent($markdown);
        $response->headers->set('Content-Type', 'text/markdown; charset=utf-8');
        $response->headers->set('Vary', 'Accept');

        $tokenEstimate = $this->estimateTokens($markdown);
        $response->headers->set('x-markdown-tokens', (string) $tokenEstimate);

        return $response;
    }

    protected function wantsMarkdown(Request $request): bool
    {
        $accept = $request->header('Accept', '');

        return str_contains($accept, 'text/markdown');
    }

    protected function convertToMarkdown(string $html): string
    {
        // Extract <main> content to skip nav/header/footer chrome
        $mainContent = $html;
        if (preg_match('/<main[^>]*>(.*?)<\/main>/s', $html, $matches)) {
            $mainContent = $matches[1];
        }

        // Extract page title from <title> tag
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/s', $html, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        // Extract meta description
        $description = '';
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/si', $html, $matches)) {
            $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        // Strip script and style tags from main content
        $mainContent = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $mainContent);
        $mainContent = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $mainContent);

        // Strip SVG elements
        $mainContent = preg_replace('/<svg\b[^>]*>.*?<\/svg>/si', '', $mainContent);

        // Strip Livewire wire: attributes to clean up output
        $mainContent = preg_replace('/\s*wire:[a-z.]+="[^"]*"/i', '', $mainContent);

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
            'remove_nodes' => 'script style svg nav button template',
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter);

        $markdown = $this->tidy($converter->convert($this->visibleContent($mainContent)));

        // Build front-matter-style header
        $header = '';
        if ($title) {
            $header .= "---\ntitle: {$title}\n";
            if ($description) {
                $header .= "description: {$description}\n";
            }
            $header .= "---\n\n";
        }

        return $header.trim($markdown)."\n";
    }

    /**
     * Drops what a visitor does not see on arrival (x-cloak popovers, hidden and aria-hidden elements) and turns
     * each question of a details list into a heading, so the answer reads under its question.
     */
    protected function visibleContent(string $html): string
    {
        $document = new DOMDocument;
        $errors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8"><div id="markdown-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($errors);

        $root = $loaded ? $document->getElementById('markdown-root') : null;

        if (! $root) {
            return $html;
        }

        $xpath = new DOMXPath($document);

        foreach (iterator_to_array($xpath->query('//*[@x-cloak or @hidden or @aria-hidden="true"]')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (iterator_to_array($xpath->query('//summary')) as $summary) {
            $heading = $document->createElement('h3');

            while ($summary->firstChild) {
                $heading->appendChild($summary->firstChild);
            }

            $summary->parentNode?->replaceChild($heading, $summary);
        }

        return implode('', array_map(fn (DOMNode $node) => $document->saveHTML($node), iterator_to_array($root->childNodes)));
    }

    /**
     * Removes decorative images and the indentation left by inline markup, makes site-relative links absolute
     * (agents read this out of context) and keeps code blocks exactly as converted.
     */
    protected function tidy(string $markdown): string
    {
        $base = rtrim((string) config('seo.canonical_url'), '/');
        $lines = [];
        $inCode = false;

        foreach (explode("\n", $markdown) as $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $inCode = ! $inCode;
                $lines[] = ltrim($line);

                continue;
            }

            if ($inCode) {
                $lines[] = $line;

                continue;
            }

            $line = preg_replace('/!\[\]\([^)]*\)/', '', $line);
            $line = preg_replace(['/\[\s+/', '/\s+\]\(/'], ['[', ']('], $line);
            $line = preg_replace('#\]\(/(?!/)#', "]({$base}/", str_replace('&amp;', '&', $line));
            $lines[] = trim(preg_replace('/[ \t]{2,}/', ' ', $line));
        }

        $markdown = preg_replace('/^```[\w-]*\n```$/m', '', implode("\n", $lines));

        return preg_replace("/\n{3,}/", "\n\n", $markdown);
    }

    protected function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 characters per token (GPT-style tokenizers)
        return (int) ceil(mb_strlen($text) / 4);
    }
}
