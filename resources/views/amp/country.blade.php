<!doctype html>
<html ⚡ lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $country->name }} VAT Rate {{ date('Y') }} — {{ \App\Models\Country::formatRate($country->standard_rate) }}% Standard | EU VAT Info</title>
    <meta name="description" content="{{ $country->name }} VAT rates {{ date('Y') }}: Standard rate {{ \App\Models\Country::formatRate($country->standard_rate) }}%{{ $country->reduced_rate ? ', reduced rate '.\App\Models\Country::formatRate($country->reduced_rate).'%' : '' }}. Currency: {{ $country->currency_code ?? 'EUR' }}. Find all {{ $country->name }} VAT rates and calculate VAT.">
    <link rel="canonical" href="{{ url('/vat-calculator/' . $country->slug) }}">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebPage",
        "name": "{{ $country->name }} VAT Rate {{ date('Y') }}",
        "description": "Current VAT rates for {{ $country->name }}: standard {{ \App\Models\Country::formatRate($country->standard_rate) }}%{{ $country->reduced_rate ? ', reduced '.\App\Models\Country::formatRate($country->reduced_rate).'%' : '' }}",
        "url": "{{ url('/amp/vat-calculator/' . $country->slug) }}",
        "isPartOf": {
            "@type": "WebSite",
            "name": "EU VAT Info",
            "url": "{{ url('/') }}"
        },
        "about": {
            "@type": "Country",
            "name": "{{ $country->name }}"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}" },
            { "@type": "ListItem", "position": 2, "name": "{{ $country->is_eu_member ? 'VAT Rates' : 'VAT Calculator' }}", "item": "{{ $country->is_eu_member ? url('/amp/vat-rates') : url('/vat-calculator') }}" },
            { "@type": "ListItem", "position": 3, "name": "{{ $country->name }}", "item": "{{ url('/amp/vat-calculator/' . $country->slug) }}" }
        ]
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
        .container { max-width: 800px; margin: 0 auto; padding: 24px 16px; }
        .breadcrumb { font-size: 0.85rem; color: #64748b; margin-bottom: 20px; }
        .breadcrumb a { color: #2563eb; }
        .breadcrumb span { margin: 0 6px; }
        .hero { background: #fff; border-radius: 12px; padding: 28px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 24px; }
        .hero-flag { font-size: 3rem; margin-bottom: 8px; }
        h1 { font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-bottom: 6px; }
        .hero-sub { color: #64748b; font-size: 0.95rem; margin-bottom: 20px; }
        .rates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .rate-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; text-align: center; }
        .rate-card.highlight { background: #1e3a8a; border-color: #1e3a8a; color: #fff; }
        .rate-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 4px; }
        .rate-card.highlight .rate-label { color: #93c5fd; }
        .rate-value { font-size: 1.6rem; font-weight: 800; color: #1e3a8a; }
        .rate-card.highlight .rate-value { color: #fff; }
        .rate-value.na { color: #94a3b8; font-size: 1.3rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 28px 0 14px; }
        .info-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .info-row:last-child { border-bottom: none; }
        .info-key { color: #64748b; }
        .info-val { font-weight: 600; color: #1e293b; }
        .table-wrap { overflow-x: auto; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        table { width: 100%; border-collapse: collapse; background: #fff; font-size: 0.88rem; }
        thead th { background: #1e3a8a; color: #fff; padding: 10px 14px; text-align: left; font-weight: 600; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td { padding: 9px 14px; border-bottom: 1px solid #e2e8f0; }
        td.rate { font-weight: 700; color: #1e3a8a; }
        .cta-btn { display: inline-block; background: #2563eb; color: #fff; font-weight: 700; padding: 12px 28px; border-radius: 6px; font-size: 1rem; margin-top: 20px; }
        .cta-btn:hover { background: #1d4ed8; text-decoration: none; }
        footer { background: #1e293b; color: #94a3b8; text-align: center; padding: 20px 16px; font-size: 0.85rem; margin-top: 40px; }
        footer a { color: #60a5fa; }
        @media (max-width: 480px) {
            h1 { font-size: 1.3rem; }
            .rates-grid { grid-template-columns: repeat(2, 1fr); }
            .rate-value { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <header>
        <a href="{{ url('/') }}">EU VAT Info</a>
        <nav>
            @if($country->is_eu_member)
            <a href="{{ url('/amp/vat-rates') }}">← All EU Rates</a>
            @endif
            <a class="header-cta" href="{{ url('/vat-calculator/' . $country->slug) }}">Open Full Calculator →</a>
        </nav>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="{{ url('/amp') }}">Home</a>
            <span>›</span>
            @if($country->is_eu_member)
                <a href="{{ url('/amp/vat-rates') }}">VAT Rates</a>
            @else
                <a href="{{ url('/vat-calculator') }}">VAT Calculator</a>
            @endif
            <span>›</span>
            <strong>{{ $country->name }}</strong>
        </div>

        <div class="hero">
            <div class="hero-flag">{{ $country->flag }}</div>
            <h1>{{ $country->name }} VAT Rate {{ date('Y') }}</h1>
            <p class="hero-sub">Current VAT rates for {{ $country->name }}. Standard rate: <strong>{{ \App\Models\Country::formatRate($country->standard_rate) }}%</strong>. Currency: {{ $country->currency ?? 'Euro' }} ({{ $country->currency_code ?? 'EUR' }}).</p>

            <div class="rates-grid">
                <div class="rate-card highlight">
                    <div class="rate-label">Standard Rate</div>
                    <div class="rate-value">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</div>
                </div>
                @if($country->reduced_rate)
                <div class="rate-card">
                    <div class="rate-label">Reduced Rate</div>
                    <div class="rate-value">{{ \App\Models\Country::formatRate($country->reduced_rate) }}%</div>
                </div>
                @endif
                @if($country->super_reduced_rate)
                <div class="rate-card">
                    <div class="rate-label">Super-Reduced</div>
                    <div class="rate-value">{{ \App\Models\Country::formatRate($country->super_reduced_rate) }}%</div>
                </div>
                @endif
                @if($country->zero_rate !== null)
                <div class="rate-card">
                    <div class="rate-label">Zero Rate</div>
                    <div class="rate-value">{{ \App\Models\Country::formatRate($country->zero_rate) }}%</div>
                </div>
                @endif
                @if($country->parking_rate)
                <div class="rate-card">
                    <div class="rate-label">Parking Rate</div>
                    <div class="rate-value">{{ \App\Models\Country::formatRate($country->parking_rate) }}%</div>
                </div>
                @endif
            </div>

            <div style="text-align:center;margin-top:20px;">
                <a class="cta-btn" href="{{ url('/vat-calculator/' . $country->slug) }}">Open Full {{ $country->name }} VAT Calculator →</a>
            </div>
        </div>

        <div class="section-title">Country Details</div>
        <div class="info-card">
            <div class="info-row"><span class="info-key">Country</span><span class="info-val">{{ $country->name }}{{ $country->native_name ? ' ('.$country->native_name.')' : '' }}</span></div>
            <div class="info-row"><span class="info-key">ISO Code</span><span class="info-val">{{ $country->iso_code }}</span></div>
            <div class="info-row"><span class="info-key">Currency</span><span class="info-val">{{ $country->currency ?? 'Euro' }} ({{ $country->currency_code ?? 'EUR' }}){{ $country->currency_symbol ? ' — '.$country->currency_symbol : '' }}</span></div>
            <div class="info-row"><span class="info-key">EU Member</span><span class="info-val">{{ $country->is_eu_member ? 'Yes' : 'No' }}</span></div>
            @if($country->is_eu_member)
                <div class="info-row"><span class="info-key">VIES Available</span><span class="info-val">{{ $country->vies_available ? 'Yes' : 'No' }}</span></div>
            @endif
        </div>

        @if($country->is_eu_member && $vatRates->count() > 0)
        <div class="section-title">VAT Rate History</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Rate</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vatRates->take(20) as $rate)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $rate->type)) }}</td>
                        <td class="rate">{{ \App\Models\Country::formatRate($rate->rate) }}%</td>
                        <td>{{ $rate->effective_from ? $rate->effective_from->format('Y-m-d') : '—' }}</td>
                        <td>{{ $rate->effective_to ? $rate->effective_to->format('Y-m-d') : 'Present' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

    <footer>
        @if($country->is_eu_member)
            <p>Data sourced from the European Commission. &copy; {{ date('Y') }} <a href="{{ url('/') }}">EU VAT Info</a></p>
        @else
            <p>Maintained VAT rate data. Verify compliance with the local tax authority. &copy; {{ date('Y') }} <a href="{{ url('/') }}">EU VAT Info</a></p>
        @endif
    </footer>
</body>
</html>
