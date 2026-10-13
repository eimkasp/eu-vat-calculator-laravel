@section('seo')
    <x-seo-meta :title="__('ui.changelog.meta_title')" :description="__('ui.changelog.meta_desc')" :url="url(locale_path('/changelog'))">
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.site_name'), 'item' => url(locale_path('/'))],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.changelog.nav_label'), 'item' => url(locale_path('/changelog'))],
            ],
        ]" />
        <x-json-ld :data="[
            '@type' => 'SoftwareApplication',
            'name' => __('ui.site_name'),
            'url' => url('/'),
            'applicationCategory' => 'FinanceApplication',
            'operatingSystem' => 'All',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
            'softwareVersion' => collect($releases)->firstWhere(fn ($release) => $release['version'] !== 'Unreleased')['version'] ?? '1.0.0',
            'releaseNotes' => url(locale_path('/changelog')),
            'dateModified' => $releases[0]['date'] ?? now()->toDateString(),
        ]" />
    </x-seo-meta>
@endsection

@php
    $groupStyles = [
        'added' => 'bg-success-soft text-success',
        'improved' => 'bg-action-soft text-action-deep',
        'changed' => 'bg-action-soft text-action-deep',
        'fixed' => 'bg-warning-soft text-warning',
        'security' => 'bg-danger-soft text-danger',
        'removed' => 'bg-surface-muted text-ink-muted',
    ];
@endphp

<div>
    <x-page-header :title="__('ui.changelog.heading')" :description="__('ui.changelog.subheading')" :eyebrow="__('ui.changelog.eyebrow')" :breadcrumbs="[__('ui.changelog.nav_label') => '']" />

    <div class="app-container py-8 sm:py-10">
        <div class="max-w-3xl">
            @if(app()->getLocale() !== 'en')
                <p class="mb-8 inline-flex items-center gap-2 text-sm text-ink-muted"><x-ui.icon name="languages" class="size-4" />{{ __('ui.changelog.english_only') }}</p>
            @endif

            @if(empty($releases))
                <p class="app-surface p-10 text-center text-sm text-ink-muted">{{ __('ui.changelog.empty') }}</p>
            @else
                <ol class="relative ml-2 space-y-12 border-l border-line pl-8" lang="en">
                    @foreach($releases as $release)
                        @php($unreleased = $release['version'] === 'Unreleased')
                        <li id="{{ $release['slug'] }}" class="relative scroll-mt-24">
                            <span @class(['absolute top-1 -left-[2.4rem] size-4 rounded-full ring-4 ring-workspace', 'bg-warning' => $unreleased, 'bg-action' => ! $unreleased]) aria-hidden="true"></span>

                            <header>
                                <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                    @if($unreleased)
                                        <span class="font-semibold text-warning">{{ __('ui.changelog.unreleased') }}</span>
                                    @else
                                        <a href="#{{ $release['slug'] }}" class="font-semibold text-action hover:underline">v{{ $release['version'] }}</a>
                                    @endif
                                    @if($release['date'])
                                        <span class="text-ink-quiet" aria-hidden="true">·</span>
                                        <time datetime="{{ $release['date'] }}" class="tabular text-ink-muted">{{ \Illuminate\Support\Carbon::parse($release['date'])->translatedFormat('j M Y') }}</time>
                                    @endif
                                </p>
                                <h2 class="mt-1.5 text-xl font-bold text-ink">{{ $release['subtitle'] ?: ($unreleased ? __('ui.changelog.in_progress') : 'v'.$release['version']) }}</h2>
                            </header>

                            @if(! empty($release['groups']))
                                <div class="mt-5 space-y-6">
                                    @foreach($release['groups'] as $group)
                                        @php($key = strtolower($group['name']))
                                        <section>
                                            <h3 class="inline-flex rounded-control px-2 py-0.5 text-xs font-semibold {{ $groupStyles[$key] ?? 'bg-surface-muted text-ink' }}">
                                                {{ \Illuminate\Support\Facades\Lang::has('ui.changelog.group_'.$key) ? __('ui.changelog.group_'.$key) : $group['name'] }}
                                            </h3>
                                            @if(! empty($group['items']))
                                                <ul class="mt-3 space-y-2.5">
                                                    @foreach($group['items'] as $item)
                                                        @if($item['type'] === 'heading')
                                                            <li class="pt-2 text-xs font-semibold text-ink-muted">{{ $item['text'] }}</li>
                                                        @else
                                                            <li class="flex items-start gap-3 text-[0.9375rem] leading-7 text-ink-muted">
                                                                <span class="mt-3 size-1 shrink-0 rounded-full bg-ink-quiet" aria-hidden="true"></span>
                                                                <span class="min-w-0 [overflow-wrap:anywhere] [&_a]:text-action [&_a]:underline [&_code]:rounded-xs [&_code]:bg-surface-muted [&_code]:px-1 [&_strong]:font-semibold [&_strong]:text-ink">{!! Str::inlineMarkdown($item['text'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</span>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </section>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-4 text-sm text-ink-muted">{{ __('ui.changelog.no_details') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>
