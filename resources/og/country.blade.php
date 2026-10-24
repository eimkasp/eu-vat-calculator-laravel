{{-- The social image of a country page, drawn at 1200×630 by App\Support\Seo\CountryOgImage. Bump its TEMPLATE_VERSION after changing this file. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><style>
{!! $fonts !!}
:root{--paper:#f8fbfe;--surface:#fdfeff;--line:#dadee4;--ink:#111825;--ink-muted:#3c434e;--ink-quiet:#5d646e;--cobalt:#0462d3;--glyph:#d3deee;--glyph-strong:#a3badd}
*{box-sizing:border-box;margin:0}
html,body{width:1200px;height:630px}
body{background:var(--paper);font-family:Plex,sans-serif;color:var(--ink);position:relative;overflow:hidden;-webkit-font-smoothing:antialiased}
.field{position:absolute;right:40px;top:30px;font:600 9.6px/11px JBMono,monospace;color:var(--glyph);white-space:pre}
.field i{font-style:normal;color:var(--glyph-strong)}
.field b{font-weight:700;color:var(--cobalt)}
.wrap{position:absolute;left:0;top:0;bottom:0;width:700px;padding:52px 0 0 72px}
.brand{display:flex;align-items:center;gap:14px;font:600 26px Familjen,Plex,sans-serif;letter-spacing:-.02em}
.brand svg{width:44px;height:44px}
.country{display:flex;align-items:center;gap:22px;margin-top:40px}
.country img{width:68px;height:51px;border-radius:5px;box-shadow:0 0 0 1px rgba(17,24,37,.14);object-fit:cover}
.country h1{font:600 {{ $nameSize }}px/1 Familjen,Plex,sans-serif;letter-spacing:-.03em;white-space:nowrap;max-width:600px;overflow:hidden;text-overflow:ellipsis}
.rate{display:flex;align-items:flex-start;margin-top:10px;font-family:Familjen,Plex,sans-serif;font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:-.05em;line-height:.84}
.rate .n{font-size:196px}
.rate .p{font-size:96px;margin:16px 0 0 6px;color:var(--cobalt)}
.label{margin-top:12px;font:500 26px Familjen,Plex,sans-serif;color:var(--ink-muted)}
.facts{position:absolute;left:72px;right:72px;bottom:76px;display:grid;grid-template-columns:repeat(4,1fr);background:var(--surface);border:1px solid var(--line);border-radius:16px 4px 16px 4px}
.facts div{padding:16px 22px 15px;border-left:1px solid var(--line);min-width:0}
.facts div:first-child{border-left:0}
.facts b{display:block;font:600 34px/1.1 Familjen,Plex,sans-serif;letter-spacing:-.02em;font-variant-numeric:tabular-nums;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.facts b.none{color:var(--ink-quiet);font-weight:500}
.facts span{display:block;margin-top:4px;font:500 17px/1.25 Familjen,Plex,sans-serif;color:var(--ink-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.foot{position:absolute;left:72px;right:72px;bottom:30px;display:flex;justify-content:space-between;gap:32px;font:500 17px Familjen,Plex,sans-serif;color:var(--ink-muted);white-space:nowrap}
.foot span{min-width:0;overflow:hidden;text-overflow:ellipsis}
.foot .url{flex-shrink:0;font:500 17px JBMono,monospace;color:var(--ink)}
</style></head><body>
<div class="field" aria-hidden="true">{!! implode("\n", $field) !!}</div>
<div class="wrap">
    <div class="brand"><svg viewBox="0 0 32 32" aria-hidden="true"><path fill="#111825" d="M8 0h18l6 6v18a8 8 0 0 1-8 8H6l-6-6V8a8 8 0 0 1 8-8Z"/><path d="M10.5 21.5 21.5 10.5" stroke="#f8fbfe" stroke-width="2.75" stroke-linecap="round"/><circle cx="11.25" cy="11.25" r="3.25" fill="#f8fbfe"/><circle cx="20.75" cy="20.75" r="3.25" fill="#0462d3"/></svg>EU VAT Info</div>
    <div class="country">
        @if($flag)<img src="{{ $flag }}" alt="">@endif
        <h1>{{ $name }}</h1>
    </div>
    <div class="rate"><span class="n">{{ $standard }}</span><span class="p">%</span></div>
    <p class="label">{{ __('ui.history.standard_rate') }}</p>
</div>
<div class="facts">
    @foreach($facts as $fact)
        <div><b @class(['none' => $fact['value'] === null])>{{ $fact['value'] ?? '—' }}</b><span>{{ $fact['label'] }}</span></div>
    @endforeach
</div>
<div class="foot">
    <span>{{ collect([$updated, $source])->filter()->implode(' · ') }}</span>
    <span class="url">{{ $host }}</span>
</div>
</body></html>
