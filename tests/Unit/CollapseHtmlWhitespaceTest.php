<?php

use App\Http\Middleware\CollapseHtmlWhitespace;

it('shortens indentation that touches a tag', function () {
    $html = "<ul>\n        <li>\n            One\n        </li>    <li>Two</li>\n</ul>";

    expect(CollapseHtmlWhitespace::collapse($html))->toBe("<ul>\n<li>\nOne\n</li> <li>Two</li>\n</ul>");
});

it('keeps single spaces and whitespace inside text', function () {
    $html = '<p>VAT  at <b>19%</b> applies</p>';

    expect(CollapseHtmlWhitespace::collapse($html))->toBe($html);
});

it('leaves pre, textarea, script and style elements as written', function () {
    $html = "<div>\n    <pre>  a\n    b</pre>\n    <textarea>\n  x  </textarea>\n    <script>\n    if (a  >  b) {}\n</script><style>\n  p {}\n</style>\n</div>";

    expect(CollapseHtmlWhitespace::collapse($html))
        ->toContain("<pre>  a\n    b</pre>")
        ->toContain("<textarea>\n  x  </textarea>")
        ->toContain("<script>\n    if (a  >  b) {}\n</script>")
        ->toContain("<style>\n  p {}\n</style>")
        ->toStartWith("<div>\n<pre>");
});

it('keeps a line break wherever one was, so inline scripts in attributes keep their statements apart', function () {
    $html = "<div x-data=\"{ open: false }\"\n     x-on:click=\"open = true\">\n  <span>Menu</span>\n</div>";

    expect(CollapseHtmlWhitespace::collapse($html))->toBe("<div x-data=\"{ open: false }\"\n     x-on:click=\"open = true\">\n<span>Menu</span>\n</div>");
});
