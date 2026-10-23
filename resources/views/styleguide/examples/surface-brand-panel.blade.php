<div class="grid max-w-lg grid-cols-3 gap-3">
    @foreach([['Standard', '19%'], ['Reduced', '7%'], ['Currency', 'EUR']] as [$label, $value])
        <dl class="app-brand-panel flex flex-col-reverse p-3 text-center">
            <dt class="font-display text-[0.8125rem] font-medium text-white/75">{{ $label }}</dt>
            <dd class="tabular font-display text-2xl font-semibold text-white">{{ $value }}</dd>
        </dl>
    @endforeach
</div>
