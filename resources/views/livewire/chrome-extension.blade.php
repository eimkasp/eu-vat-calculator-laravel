@section('seo')
    <x-seo-meta
        title="EU VAT Calculator — Free Chrome Extension"
        description="Calculate EU VAT instantly from your browser toolbar. Free Chrome extension with all 27 EU country rates, calculation history, new-tab mode, and offline support."
        type="website" />
@endsection

@php
    $features = [
        ['zap', 'Instant calculation', 'Add or extract VAT with one click. Results appear instantly in the popup — no page loads, no waiting.'],
        ['flag', 'All 27 EU countries', 'Standard, reduced, super-reduced and parking rates for every EU member state. Rates sync from vat.businesspress.io every 12 hours.'],
        ['monitor', 'New tab mode', 'Replace your Chrome new tab with a full VAT calculator, a live clock and a quick rate overview for your default country.'],
        ['history', 'Calculation history', 'Your last 20 calculations are saved automatically. Select any past calculation to restore it instantly.'],
        ['wifi-off', 'Works offline', 'Built-in rate data keeps the calculator working without an internet connection. Rates sync again when you are back online.'],
        ['keyboard', 'Keyboard shortcuts', null],
    ];
    $permissions = [
        ['storage', 'Save settings, history and cached rates'],
        ['alarms', 'Refresh rates every 12 hours'],
        ['contextMenus', 'Right-click “Open in full tab”'],
    ];
@endphp

<div>
    <section class="hero-canvas">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
            <x-site-breadcrumbs :items="[__('ui.breadcrumbs.tools') => locale_path('/tools'), 'Chrome extension' => '']" variant="dark" />
            <div class="mt-6 max-w-3xl">
                <p class="app-kicker">Free Chrome extension</p>
                <h1 class="mt-3 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">EU VAT calculator for Chrome</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">Calculate EU VAT from your browser toolbar. All 27 EU country rates, calculation history and a new-tab mode, always one click away.</p>
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener noreferrer" class="mt-8 inline-block rounded-card transition-opacity hover:opacity-90">
                    <img src="/images/chrome-web-store-badge.png" alt="Available in the Chrome Web Store" class="h-14 sm:h-16" loading="eager">
                <x-ui.new-tab /></a>
            </div>
        </div>
    </section>

    <div class="app-container space-y-10 py-8 sm:py-10">
        <section aria-labelledby="extension-features">
            <h2 id="extension-features" class="app-eyebrow">Features</h2>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($features as [$icon, $title, $text])
                    <li class="app-surface p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true">
                                <x-ui.icon :name="$icon" class="size-5" />
                            </span>
                            <h3 class="text-base font-bold text-ink">{{ $title }}</h3>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-ink-muted">
                            @if($text)
                                {{ $text }}
                            @else
                                <kbd class="app-kbd">⌘ ↵</kbd> to calculate and <kbd class="app-kbd">Esc</kbd> to clear. European number formats such as 1.234,56 are fully supported.
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="extension-privacy">
            <h2 id="extension-privacy" class="app-eyebrow">Privacy and permissions</h2>
            <div class="app-surface mt-4 grid grid-cols-1 divide-y divide-line sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <div class="p-5 sm:p-6">
                    <h3 class="flex items-center gap-2 font-bold text-ink">
                        <x-ui.icon name="shield-check" class="size-5 text-success" />
                        Your data stays on your device
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-ink-muted">
                        @foreach(['No user data is collected or transmitted', 'Every calculation runs locally in your browser', 'History is stored only in local Chrome storage'] as $point)
                            <li class="flex items-start gap-2">
                                <x-ui.icon name="check" class="mt-0.5 size-4 text-success" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="p-5 sm:p-6">
                    <h3 class="font-bold text-ink">Permissions used</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        @foreach($permissions as [$permission, $purpose])
                            <div class="flex items-start gap-3">
                                <dt><code class="rounded-xs bg-surface-muted px-1.5 py-0.5 font-mono text-xs text-ink">{{ $permission }}</code></dt>
                                <dd class="text-ink-muted">{{ $purpose }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <section class="hero-canvas rounded-panel p-6 sm:p-8" aria-labelledby="extension-cta">
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="extension-cta" class="text-xl font-bold text-white">Get the EU VAT calculator for Chrome</h2>
                    <p class="mt-1 text-sm text-white/85">Free, fast and private. Calculate VAT in seconds from any tab.</p>
                </div>
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener noreferrer" class="inline-block shrink-0 rounded-card transition-opacity hover:opacity-90">
                    <img src="/images/chrome-web-store-badge.png" alt="Available in the Chrome Web Store" class="h-14" loading="lazy">
                <x-ui.new-tab /></a>
            </div>
        </section>
    </div>
</div>
