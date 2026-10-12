<!doctype html>
<html ⚡ lang="en">
<head>
    <meta charset="utf-8">
    <title>EU VAT Rates List {{ date('Y') }} — All Member States | EU VAT Info</title>
    <meta name="description" content="Complete EU VAT rate list for {{ date('Y') }}. Standard, reduced, super-reduced, and parking rates for all 27 EU member states.">
    <link rel="canonical" href="{{ url('/amp/vat-rates') }}">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Table",
        "about": {
            "@type": "Dataset",
            "name": "EU VAT Rates {{ date('Y') }}",
            "description": "Standard, reduced, super-reduced, and parking VAT rates for all 27 EU member states",
            "url": "{{ url('/amp/vat-rates') }}",
            "license": "https://creativecommons.org/licenses/by/4.0/",
            "creator": { "@type": "Organization", "name": "EU VAT Info", "url": "{{ url('/') }}" }
        }
    }
    </script>
    <style amp-custom>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #1e293b; font-size: 16px; line-height: 1.5; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        header { background: #1e3a8a; color: #fff; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        header a { color: #fff; font-weight: 700; font-size: 1.2rem; }
        header nav { display: flex; align-items: center; gap: 12px; }
        header nav a { font-size: 0.9rem; opacity: 0.85; }
        .header-cta { display: inline-block; background: #f59e0b; color: #1e293b; font-weight: 700; padding: 7px 16px; border-radius: 6px; font-size: 0.85rem; white-space: nowrap; opacity: 1; }
        .header-cta:hover { background: #d97706; text-decoration: none; }
        .container { max-width: 960px; margin: 0 auto; padding: 24px 16px; }
        h1 { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .subtitle { color: #64748b; margin-bottom: 24px; font-size: 0.95rem; }
        .table-wrap { overflow-x: auto; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        table { width: 100%; border-collapse: collapse; background: #fff; font-size: 0.88rem; }
        thead th { background: #1e3a8a; color: #fff; padding: 12px 14px; text-align: left; font-weight: 600; white-space: nowrap; }
        thead th:first-child { min-width: 160px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        td.rate { font-weight: 700; color: #1e3a8a; text-align: right; }
        td.rate-secondary { color: #475569; text-align: right; }
        td.rate-none { color: #94a3b8; text-align: right; }
        td.country-name a { font-weight: 600; color: #1e293b; }
        td.country-name a:hover { color: #2563eb; }
        .calc-link { display: inline-block; font-size: 0.75rem; font-weight: 600; color: #2563eb; white-space: nowrap; }
        .flag { font-size: 1.25rem; margin-right: 6px; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; background: #dbeafe; color: #1d4ed8; }
        .info-box { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 4px; margin-bottom: 24px; font-size: 0.9rem; color: #1e40af; }
        footer { background: #1e293b; color: #94a3b8; text-align: center; padding: 20px 16px; font-size: 0.85rem; margin-top: 40px; }
        footer a { color: #60a5fa; }
        @media (max-width: 600px) {
            h1 { font-size: 1.3rem; }
            td, thead th { padding: 8px 9px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <header>
        <a href="{{ url('/') }}">EU VAT Info</a>
        <nav>
            <a href="{{ url('/amp') }}">← All Rates</a>
            <a class="header-cta" href="{{ url('/vat-calculator') }}">Open Full Calculator →</a>
        </nav>
    </header>

    <div class="container">
        <h1>EU VAT Rates — Complete List {{ date('Y') }}</h1>
        <p class="subtitle">Standard, reduced, super-reduced, and parking VAT rates for all 27 EU member states. Last updated {{ now()->format('F j, Y') }}.</p>

        <div class="info-box">
            Rates are sourced from the European Commission. Click a country name to view detailed information and use the VAT calculator.
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>ISO</th>
                        <th>Standard</th>
                        <th>Reduced</th>
                        <th>Super-Red.</th>
                        <th>Zero</th>
                        <th>Parking</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $country)
                    <tr>
                        <td class="country-name">
                            <span class="flag">{{ $country->flag }}</span>
                            <a href="{{ url('/amp/vat-calculator/' . $country->slug) }}">{{ $country->name }}</a><br>
                            <a class="calc-link" href="{{ url('/vat-calculator/' . $country->slug) }}">Calculate →</a>
                        </td>
                        <td><span class="badge">{{ $country->iso_code }}</span></td>
                        <td class="rate">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</td>
                        <td class="{{ $country->reduced_rate ? 'rate-secondary' : 'rate-none' }}">{{ $country->reduced_rate ? $country->reduced_rate.'%' : '—' }}</td>
                        <td class="{{ $country->super_reduced_rate ? 'rate-secondary' : 'rate-none' }}">{{ $country->super_reduced_rate ? $country->super_reduced_rate.'%' : '—' }}</td>
                        <td class="{{ $country->zero_rate !== null ? 'rate-secondary' : 'rate-none' }}">{{ $country->zero_rate !== null ? $country->zero_rate.'%' : '—' }}</td>
                        <td class="{{ $country->parking_rate ? 'rate-secondary' : 'rate-none' }}">{{ $country->parking_rate ? $country->parking_rate.'%' : '—' }}</td>
                        <td><span class="badge">{{ $country->currency_code ?? 'EUR' }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>Data sourced from the European Commission. &copy; {{ date('Y') }} <a href="{{ url('/') }}">EU VAT Info</a></p>
    </footer>
</body>
</html>
