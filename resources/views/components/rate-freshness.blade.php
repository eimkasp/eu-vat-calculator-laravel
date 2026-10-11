@props(['updated' => null, 'eu' => true, 'onDark' => false])

@php
    $updated = $updated ? \Illuminate\Support\Carbon::parse($updated) : null;
    $source = $eu ? config('calculator.rates_source_url') : config('calculator.dataset_source_url');
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs font-medium', 'text-white/75' => $onDark, 'text-ink-muted' => ! $onDark]) }} data-rate-freshness>
    @if($updated)
        <span class="inline-flex items-center gap-1.5">
            <x-ui.icon name="clock" class="size-3.5 shrink-0" />
            <span>{!! __('ui.trust.updated', ['date' => '<time datetime="'.e($updated->toDateString()).'">'.e($updated->locale(app()->getLocale())->translatedFormat('j M Y')).'</time>']) !!}</span>
        </span>
    @endif
    <a href="{{ $source }}" target="_blank" rel="noopener noreferrer" @class(['inline-flex items-center gap-1.5 underline decoration-1 underline-offset-[3px] transition-colors', 'decoration-white/40 hover:text-white hover:decoration-white' => $onDark, 'decoration-line-strong hover:text-action hover:decoration-action' => ! $onDark])>
        <x-ui.icon name="shield-check" class="size-3.5 shrink-0" />
        {{ $eu ? __('ui.trust.source_ec') : __('ui.trust.source_dataset') }}
        <x-ui.icon name="arrow-up-right" class="size-3 shrink-0" />
    </a>
    {{ $slot }}
</div>
