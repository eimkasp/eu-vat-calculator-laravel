@extends('layouts.app')

@php
    $pageTitle = request()->route('country') ? 'Embed a free VAT calculator for '.$selectedCountry->name : 'Embed a free EU VAT calculator on your website';
    $config = [
        'base' => route('widget.iframe'),
        'country' => $selectedCountry->slug,
        'heights' => ['vertical' => 760, 'horizontal' => 500],
    ];
    $platforms = [
        ['WordPress', 'Add a Custom HTML block and paste the code.'],
        ['Shopify', 'Edit theme, then add a Custom Liquid section.'],
        ['Wix / Squarespace', 'Use an Embed or HTML block.'],
        ['Plain HTML', 'Paste the snippet where the widget should appear.'],
    ];
@endphp

@section('seo')
    <x-seo-meta
        :title="$pageTitle.' | EU VAT Info'"
        description="Add a free, always up-to-date EU VAT calculator to your website with one iframe snippet. No account, API key or tracking cookies required."
        :url="url('/embed')"
    />
@endsection

@section('content')
    <div x-data="embedBuilder(@js($config))">
        <x-page-header :title="$pageTitle" description="Add an always up-to-date VAT calculator to any website with a single iframe snippet. No account, API key or tracking cookies." eyebrow="Embed widget" :breadcrumbs="[__('ui.nav.vat_widget') => '']" />

        <div class="app-container space-y-8 py-8 sm:py-10">
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                <section class="app-surface p-5 sm:p-6" aria-labelledby="widget-settings">
                    <h2 id="widget-settings" class="text-lg font-bold text-ink">Widget settings</h2>

                    <label for="widget-country" class="mt-5 mb-1.5 block text-[0.8125rem] font-semibold text-ink-muted">Default country</label>
                    <select id="widget-country" x-model="country" class="app-select">
                        @foreach($countries as $option)
                            <option value="{{ $option->slug }}">{{ $option->name }}</option>
                        @endforeach
                    </select>

                    <fieldset class="mt-5">
                        <legend class="mb-1.5 text-[0.8125rem] font-semibold text-ink-muted">Layout</legend>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach(['vertical' => ['Vertical', 'Stacked, for sidebars and narrow columns'], 'horizontal' => ['Horizontal', 'Side by side, for full-width sections']] as $value => [$label, $hint])
                                <label class="relative flex cursor-pointer flex-col gap-1 rounded-card border p-4 transition-colors" :class="style === @js($value) ? 'border-action bg-action-soft' : 'border-line hover:border-line-strong'">
                                    <input type="radio" name="widget-style" value="{{ $value }}" x-model="style" class="sr-only">
                                    <span class="flex items-center justify-between text-sm font-semibold text-ink">
                                        {{ $label }}
                                        <x-ui.icon name="check-circle" class="size-4 text-action" x-show="style === '{{ $value }}'" />
                                    </span>
                                    <span class="text-xs leading-5 text-ink-muted">{{ $hint }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="mt-6">
                        <div class="mb-1.5 flex items-center justify-between">
                            <p class="text-[0.8125rem] font-semibold text-ink-muted">Embed code</p>
                            <span class="text-xs text-ink-muted">Free forever</span>
                        </div>
                        <pre class="app-code whitespace-pre-wrap break-all p-4 font-mono text-xs leading-5 text-syntax-string" x-text="code"></pre>
                        <button type="button" x-on:click="$copy(code, 'Embed code copied')" class="app-button-primary mt-3 w-full plausible-event-name=CopyCode">
                            <x-ui.icon name="copy" class="size-4" />
                            Copy embed code
                        </button>
                    </div>
                </section>

                <section class="app-surface overflow-hidden" aria-labelledby="widget-preview">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
                        <h2 id="widget-preview" class="text-lg font-bold text-ink">Live preview</h2>
                        <a :href="src" target="_blank" rel="noopener" class="app-link inline-flex items-center gap-1 text-sm">
                            Open
                            <x-ui.icon name="arrow-up-right" class="size-3.5" />
                        <x-ui.new-tab /></a>
                    </div>
                    <div class="bg-[repeating-conic-gradient(var(--ui-surface-subtle)_0_25%,var(--ui-surface)_0_50%)] bg-[length:24px_24px] p-4 sm:p-6">
                        <iframe :src="src" :style="'height:' + height + 'px'" class="mx-auto block w-full rounded-card border-0 bg-transparent transition-[max-width] duration-200" :class="style === 'vertical' ? 'max-w-md' : 'max-w-none'" title="EU VAT calculator widget preview" loading="lazy"></iframe>
                    </div>
                </section>
            </div>

            <section class="grid gap-6 lg:grid-cols-2" aria-labelledby="widget-install">
                <div class="app-surface p-5 sm:p-6">
                    <h2 id="widget-install" class="text-lg font-bold text-ink">How to install</h2>
                    <ol class="mt-4 space-y-4">
                        @foreach([
                            ['Pick a layout', 'Vertical suits sidebars and narrow columns; horizontal suits full-width sections.'],
                            ['Copy the embed code', 'It is a plain iframe snippet with no JavaScript or dependencies.'],
                            ['Paste it into your site', 'Place it wherever the calculator should appear.'],
                            ['Adjust the height if needed', 'Edit the height value in the snippet to fit your layout.'],
                        ] as $index => [$step, $detail])
                            <li class="flex gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-action-soft text-xs font-bold text-action-deep">{{ $index + 1 }}</span>
                                <span>
                                    <span class="block text-sm font-semibold text-ink">{{ $step }}</span>
                                    <span class="block text-sm leading-6 text-ink-muted">{{ $detail }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="app-surface p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-ink">Works on every platform</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($platforms as [$platform, $tip])
                            <div class="rounded-card bg-surface-subtle p-4">
                                <p class="text-sm font-semibold text-ink">{{ $platform }}</p>
                                <p class="mt-1 text-sm leading-6 text-ink-muted">{{ $tip }}</p>
                            </div>
                        @endforeach
                    </div>
                    <ul class="mt-5 space-y-2 text-sm text-ink-muted">
                        @foreach(['Free, with no account or API key', 'VAT rates update automatically from official EU sources', 'Responsive and keyboard accessible', 'No tracking cookies'] as $point)
                            <li class="flex items-center gap-2"><x-ui.icon name="check" class="size-4 text-success" />{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>
    </div>
@endsection
