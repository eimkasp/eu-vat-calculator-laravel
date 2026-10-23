@php
    $postUrl = url(locale_path('/blog/'.$post['slug']));
@endphp

@section('seo')
    <x-seo-meta :title="$post['title'].' | '.__('ui.site_name')" :description="$post['description']" :url="$postUrl" type="article">
        <x-json-ld :data="[
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => $post['description'],
            'datePublished' => $post['published_at']->toIso8601String(),
            'dateModified' => $post['updated_at']->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $post['author']],
            'publisher' => ['@type' => 'Organization', 'name' => __('ui.site_name'), 'url' => url(locale_path('/'))],
            'mainEntityOfPage' => $postUrl,
            'citation' => collect($post['sources'])->pluck('url')->values()->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <header class="app-page-header">
        <div class="app-container pb-8 pt-6 sm:pb-10">
            <x-site-breadcrumbs :items="[__('ui.nav.updates') => locale_path('/blog'), $post['title'] => '']" />
            <div class="mt-6 max-w-3xl">
                <h1 class="text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-[2.5rem] sm:leading-[1.08]">{{ $post['title'] }}</h1>
                <p class="mt-4 max-w-[68ch] text-base leading-7 text-ink-muted sm:text-lg">{{ $post['description'] }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-muted">
                    <span class="app-badge bg-action-soft text-action-deep">{{ $post['category'] }}</span>
                    <time datetime="{{ $post['published_at']->toDateString() }}">{{ __('ui.blog.published', ['date' => $post['published_at']->translatedFormat('j M Y')]) }}</time>
                    <time datetime="{{ $post['updated_at']->toDateString() }}">{{ __('ui.blog.updated', ['date' => $post['updated_at']->translatedFormat('j M Y')]) }}</time>
                    <span>{{ __('ui.blog.reading_time', ['count' => $post['reading_time']]) }}</span>
                </div>
                @if($post['tags'])
                    <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="Tags">
                        @foreach($post['tags'] as $tag)
                            <li class="app-badge bg-surface-muted text-ink-muted">{{ $tag }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(app()->getLocale() !== 'en')
                    <p class="mt-5 inline-flex items-center gap-2 text-sm text-ink-muted"><x-ui.icon name="languages" class="size-4" />{{ __('ui.blog.english_only') }}</p>
                @endif
            </div>
        </div>
    </header>

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <article class="app-surface min-w-0 p-5 sm:p-8" lang="en">
                <div class="app-prose max-w-[72ch]">
                    {!! $html !!}
                </div>
            </article>

            <aside class="space-y-6 lg:sticky lg:top-24">
                @livewire('vat-change-signup', ['source' => 'blog-'.$post['slug'], 'compact' => true])

                @if($post['sources'])
                    <section class="app-surface p-5" aria-labelledby="post-sources">
                        <h2 id="post-sources" class="text-base font-semibold text-ink">{{ __('ui.blog.sources') }}</h2>
                        <ul class="mt-3 space-y-2.5 text-sm">
                            @foreach($post['sources'] as $source)
                                <li>
                                    <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="app-link inline font-medium">
                                        {{ $source['title'] }}<x-ui.icon name="arrow-up-right" class="ml-1 inline size-3 align-[-1px]" />
                                    <x-ui.new-tab /></a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</div>
