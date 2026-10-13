@props(['position'])

@php
    $banners = \App\Models\Banner::active()->where('position', $position)->get();
@endphp

@foreach($banners as $banner)
    <div @class(['app-container py-2' => $position === 'header_top', 'my-4' => $position !== 'header_top'])>
        <a href="{{ $banner->link_url }}" target="_blank" rel="noopener noreferrer sponsored" class="block overflow-hidden rounded-card border border-line bg-surface transition-colors hover:border-line-strong">
            @if($banner->image)
                <img src="{{ Storage::disk('public')->url($banner->image) }}" alt="{{ $banner->title }}" class="w-full" loading="lazy">
            @elseif($banner->content)
                <div class="p-4 text-sm text-ink">{!! $banner->content !!}</div>
            @endif
        <x-ui.new-tab /></a>
    </div>
@endforeach
