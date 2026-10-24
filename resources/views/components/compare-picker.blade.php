@props(['selected' => [], 'invalid' => [], 'error' => null])

@php
    $countries = \App\Support\SiteNavigation::comparableCountries()->groupBy(fn ($country) => $country->is_eu_member ? 'eu' : 'other_europe');
    $fields = [
        ['label' => __('ui.compare_index.first'), 'placeholder' => __('ui.compare_index.choose'), 'required' => true],
        ['label' => __('ui.compare_index.second'), 'placeholder' => __('ui.compare_index.choose'), 'required' => true],
        ['label' => __('ui.compare_index.third'), 'placeholder' => __('ui.compare_index.no_third'), 'required' => false],
    ];
    $id = 'compare-picker-'.substr(md5(implode('-', $selected).($error ?? '')), 0, 6);

    $invalid = $error ? ($invalid ?: [0, 1]) : [];
@endphp

{{-- A plain GET form: /compare validates the choice and redirects to the one canonical comparison URL, so it works without script. --}}
<form method="get" action="{{ locale_path('/compare') }}" data-markdown-skip {{ $attributes->class(['grid grid-cols-1 items-end gap-3 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto]']) }}>
    @foreach($fields as $index => $field)
        @php($isInvalid = in_array($index, $invalid, true))
        <div>
            <label for="{{ $id }}-{{ $index }}" class="mb-1.5 block font-display text-[0.8125rem] font-medium text-ink-muted">{{ $field['label'] }}</label>
            <select
                id="{{ $id }}-{{ $index }}"
                name="countries[]"
                @class(['app-select', 'border-danger focus:border-danger focus:ring-danger/15' => $isInvalid])
                @if($field['required']) required @endif
                @if($isInvalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                @if($isInvalid && $index === $invalid[0]) autofocus @endif
            >
                <option value="">{{ $field['placeholder'] }}</option>
                @foreach($countries as $group => $members)
                    <optgroup label="{{ __('ui.calculator.groups.'.$group) }}">
                        @foreach($members as $country)
                            <option value="{{ $country->slug }}" @selected(($selected[$index] ?? null) === $country->slug)>{{ $country->name }} ({{ \App\Models\Country::formatRate($country->standard_rate) }}%)</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    @endforeach
    <button type="submit" class="app-button-primary">
        <x-ui.icon name="arrow-left-right" class="size-4" />
        {{ __('ui.compare_index.submit') }}
    </button>
    @if($error)
        <p id="{{ $id }}-error" role="alert" class="flex items-center gap-1.5 text-sm font-medium text-danger lg:col-span-4">
            <x-ui.icon name="alert-circle" class="size-4 shrink-0" />
            {{ $error }}
        </p>
    @endif
</form>
