@extends('layouts.embed')

@section('content')
    <div class="p-2 sm:p-3">
        <livewire:hero-calculator :key="'widget-'.$country.'-'.$style" :initial-country="$country" surface="embed" :variant="$style === 'horizontal' ? 'full' : 'compact'" />
        <p class="mt-2 text-right text-xs text-ink-muted">
            Powered by <a href="https://businesspress.io" target="_blank" rel="noopener" class="font-semibold text-action hover:underline">BusinessPress<x-ui.new-tab /></a>
        </p>
    </div>
@endsection
