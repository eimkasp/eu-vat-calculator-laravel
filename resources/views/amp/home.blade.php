<!doctype html>
<html ⚡ lang="en">
<head>
    <meta charset="utf-8">
    <title>EU VAT Rates {{ date('Y') }} — All 27 EU Member States | EU VAT Info</title>
    <meta name="description" content="Current VAT rates for all 27 EU member states. Compare standard, reduced, and super-reduced VAT rates across Europe.">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="amphtml" href="{{ url('/amp') }}">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebPage",
        "name": "EU VAT Rates {{ date('Y') }}",
        "description": "Current VAT rates for all 27 EU member states",
        "url": "{{ url('/amp') }}",
        "isPartOf": {
            "@type": "WebSite",
            "name": "EU VAT Info",
            "url": "{{ url('/') }}"
        }
    }
    </script>
    <style amp-custom>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #1e293b; font-size: 16px; line-height: 1.5; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        header { background: #1e3a8a; color: #fff; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        header a.site-name { color: #fff; font-weight: 700; font-size: 1.25rem; }
        .header-cta { display: inline-block; background: #f59e0b; color: #1e293b; font-weight: 700; padding: 7px 16px; border-radius: 6px; font-size: 0.85rem; white-space: nowrap; }
        .header-cta:hover { background: #d97706; text-decoration: none; }
        .container { max-width: 900px; margin: 0 auto; padding: 24px 16px; }
        h1 { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .subtitle { color: #64748b; margin-bottom: 24px; font-size: 0.95rem; }
        .table-wrap { overflow-x: auto; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        table { width: 100%; border-collapse: collapse; background: #fff; font-size: 0.9rem; }
        thead th { background: #1e3a8a; color: #fff; padding: 12px 16px; text-align: left; font-weight: 600; white-space: nowrap; }
        .calc-link { display: inline-block; font-size: 0.78rem; font-weight: 600; color: #2563eb; white-space: nowrap; }
        .calc-link:hover { text-decoration: underline; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        td { padding: 11px 16px; border-bottom: 1px solid #e2e8f0; }
        td.rate { font-weight: 700; color: #1e3a8a; }
        td.country-name a { font-weight: 600; color: #1e293b; }
        td.country-name a:hover { color: #2563eb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #dcfce7; color: #15803d; }
        footer { background: #1e293b; color: #94a3b8; text-align: center; padding: 20px 16px; font-size: 0.85rem; margin-top: 40px; }
        footer a { color: #60a5fa; }
        .flag { font-size: 1.3rem; margin-right: 6px; }
        @media (max-width: 600px) {
            h1 { font-size: 1.35rem; }
            td, thead th { padding: 9px 10px; font-size: 0.82rem; }
        }
    </style>
</head>
<body>
    <header>
        <a class="site-name" href="{{ url('/') }}">EU VAT Info</a>
        <a class="header-cta" href="{{ url('/vat-calculator') }}">Open Full Calculator →</a>
    </header>

    <div class="container">
        <h1>EU VAT Rates {{ date('Y') }}</h1>
        <p class="subtitle">Current Value Added Tax (VAT) rates for all 27 EU member states. Updated {{ now()->format('F Y') }}.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Standard Rate</th>
                        <th>Reduced Rate</th>
                        <th>Super-Reduced</th>
                        <th>Parking Rate</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $country)
                    <tr>
                        <td class="country-name">
                            <span class="flag">{{ $country->flag }}</span>
                            <a href="{{ url('/amp/vat-calculator/' . $country->slug) }}">{{ $country->name }}</a><br>
                            <a class="calc-link" href="{{ url('/vat-calculator/' . $country->slug) }}">Calculate VAT →</a>
                        </td>
                        <td class="rate">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</td>
                        <td>{{ $country->reduced_rate ? $country->reduced_rate.'%' : '—' }}</td>
                        <td>{{ $country->super_reduced_rate ? $country->super_reduced_rate.'%' : '—' }}</td>
                        <td>{{ $country->parking_rate ? $country->parking_rate.'%' : '—' }}</td>
                        <td><span class="badge">{{ $country->currency_code ?? '€' }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>Data sourced from the European Commission. &copy; {{ date('Y') }} <a href="{{ url('/') }}">EU VAT Info</a>. <a href="{{ url('/amp/vat-rates') }}">Full VAT Rate List</a></p>
    </footer>
</body>
</html>
