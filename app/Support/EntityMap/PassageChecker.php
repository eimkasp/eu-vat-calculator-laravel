<?php

namespace App\Support\EntityMap;

/**
 * Checks that every passage of the entity map still appears on the page it cites.
 */
class PassageChecker
{
    /**
     * @param  callable(string): ?string  $fetch  Returns the body of a path, or null when it cannot be read.
     * @return list<string> One message per passage that is missing or on an unreadable page.
     */
    public function problems(callable $fetch): array
    {
        $problems = [];
        $pages = [];

        foreach (EntityMap::passages() as $passage) {
            $url = $passage['url'];
            $pages[$url] ??= self::normalise((string) $fetch($url));

            if ($pages[$url] === '') {
                $problems[] = "{$passage['entity']}: {$url} could not be read.";
            } elseif (! str_contains($pages[$url], self::normalise($passage['text']))) {
                $problems[] = "{$passage['entity']}: passage not found on {$url}: {$passage['text']}";
            }
        }

        return $problems;
    }

    public static function normalise(string $body): string
    {
        $text = preg_replace('#<(script|style)\b.*?</\1>#is', ' ', $body) ?? $body;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strtr($text, ['’' => "'", '‘' => "'", '“' => '"', '”' => '"', "\u{00A0}" => ' ']);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
