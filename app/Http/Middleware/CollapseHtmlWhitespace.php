<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Collapses the indentation Blade leaves between tags, which is a quarter to a third of a page. Only runs of two or
 * more spaces, tabs or line breaks that touch a tag are shortened, to one line break or one space, so the page
 * renders exactly as before. Attribute values away from a tag edge, and pre, textarea, script and style elements,
 * are left as written.
 */
class CollapseHtmlWhitespace
{
    private const PRESERVED = '#(<(pre|textarea|script|style)\b.*?</\2\s*>)#is';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isHtml($response)) {
            return $response;
        }

        $content = $response->getContent();

        if (is_string($content) && $content !== '') {
            $response->setContent(self::collapse($content));
        }

        return $response;
    }

    public static function collapse(string $html): string
    {
        $parts = preg_split(self::PRESERVED, $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $html;
        }

        $output = '';
        $last = count($parts) - 1;

        foreach ($parts as $i => $part) {
            if ($i % 3 === 1) {
                $output .= $part;
            } elseif ($i % 3 === 0) {
                $afterPreserved = $i > 0 ? '|^' : '';
                $beforePreserved = $i < $last ? '|$' : '';

                $output .= preg_replace_callback(
                    "/(?:(?<=>){$afterPreserved})[ \t\r\n]{2,}|[ \t\r\n]{2,}(?:(?=<){$beforePreserved})/",
                    fn (array $match) => str_contains($match[0], "\n") ? "\n" : ' ',
                    $part,
                ) ?? $part;
            }
        }

        return $output;
    }

    private function isHtml(Response $response): bool
    {
        return ! $response instanceof StreamedResponse
            && ! $response instanceof BinaryFileResponse
            && $response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
