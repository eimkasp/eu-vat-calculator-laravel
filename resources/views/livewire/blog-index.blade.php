@section('seo')
    <x-seo-meta :title="__('ui.blog.meta_title')" :description="__('ui.blog.meta_description')" :url="url(locale_path('/blog'))">
        <x-json-ld :data="[
            '@type' => 'Blog',
            'name' => __('ui.blog.meta_title'),
            'description' => __('ui.blog.meta_description'),
            'url' => url(locale_path('/blog')),
            'publisher' => ['@type' => 'Organization', 'name' => __('ui.site_name'), 'url' => url(locale_path('/'))],
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.blog.title')" :description="__('ui.blog.description')" :eyebrow="__('ui.blog.eyebrow')" :breadcrumbs="[__('ui.nav.updates') => '']" />

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-4" aria-label="{{ __('ui.blog.eyebrow') }}">
                @forelse($posts as $post)
                    <article class="group app-surface relative p-5 transition-[border-color,box-shadow] hover:border-action/40 hover:shadow-workflow sm:p-6">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-muted">
                            <span class="app-badge bg-action-soft text-action-deep">{{ $post['category'] }}</span>
                            <time datetime="{{ $post['published_at']->toDateString() }}">{{ $post['published_at']->translatedFormat('j M Y') }}</time>
                            <span aria-hidden="true">·</span>
                            <span>{{ __('ui.blog.reading_time', ['count' => $post['reading_time']]) }}</span>
                        </div>
                        <h2 class="mt-3 text-xl font-bold text-ink sm:text-2xl">
                            <a href="{{ locale_path('/blog/'.$post['slug']) }}" class="after:absolute after:inset-0 group-hover:text-action">{{ $post['title'] }}</a>
                        </h2>
                        <p class="mt-2 max-w-[72ch] text-sm leading-6 text-ink-muted sm:text-base sm:leading-7">{{ $post['description'] }}</p>
                        @if($post['tags'])
                            <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="Tags">
                                @foreach($post['tags'] as $tag)
                                    <li class="rounded-control bg-surface-muted px-2 py-0.5 text-xs font-medium text-ink-muted">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-action">
                            {{ __('ui.blog.read_guide') }}
                            <x-ui.icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
                        </span>
                    </article>
                @empty
                    <p class="app-surface p-10 text-center text-sm text-ink-muted">{{ __('ui.blog.empty') }}</p>
                @endforelse

                <livewire:vat-rate-changes />
            </section>

            <aside class="lg:sticky lg:top-24">
                @livewire('vat-change-signup', ['source' => 'blog-index', 'compact' => true])
            </aside>
        </div>
    </div>
</div>
