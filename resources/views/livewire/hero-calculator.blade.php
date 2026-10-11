@php
    $locale = str_replace('_', '-', app()->getLocale());
    $compact = $variant === 'compact';
    $embedded = $surface === 'embed';
    $onDark = ! in_array($surface, ['workspace', 'embed'], true);
    $id = $this->getId();
    $money = fn (float $value) => \App\Support\Vat\Money::format($value, $currency);
    $config = [
        'mode' => $mode,
        'amount' => $amount,
        'rate' => $selectedRate,
        'customRate' => $customRate,
        'currency' => $currency,
        'currencySymbol' => $currencySymbol,
        'locale' => $locale,
        'countries' => $this->countries,
        'templates' => [
            'exclude' => __('ui.calculator.summary_add'),
            'include' => __('ui.calculator.summary_remove'),
        ],
        'rateDescriptions' => collect(['standard', 'reduced', 'super_reduced', 'parking', 'custom'])->mapWithKeys(fn ($type) => [$type => __('ui.rate_type_desc.'.$type)])->all(),
    ];
    $groups = ['eu' => __('ui.calculator.groups.eu'), 'other_europe' => __('ui.calculator.groups.other_europe')];
@endphp

<div class="w-full" data-calculator-surface="{{ $surface }}" x-data="vatCalculator(@js($config))">
    @if(empty($this->countries) || ! $country)
        <div data-calculator-unavailable role="status" class="app-surface mx-auto max-w-4xl px-6 py-10 text-center">
            <h2 class="text-xl font-bold text-ink">{{ __('ui.calculator.unavailable_title') }}</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-ink-muted">{{ __('ui.calculator.unavailable_desc') }}</p>
        </div>
    @else
        <div id="hero-calculator" @class([
            'scroll-mb-24 app-surface-raised text-ink md:scroll-mb-6',
            'mx-auto max-w-5xl' => ! $compact,
            'grid md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]' => ! $compact,
        ])>
            {{-- Inputs --}}
            <div @class(['flex flex-col gap-5 p-5', 'sm:p-7' => ! $compact])>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div role="radiogroup" aria-label="{{ __('ui.calculator.mode_label') }}" class="app-segmented" data-value="{{ $mode }}" :data-value="mode">
                        <span class="app-segmented-thumb" aria-hidden="true"></span>
                        @foreach(['exclude' => ['plus', __('ui.calculator.add_vat_mode')], 'include' => ['minus', __('ui.calculator.remove_vat_mode')]] as $value => [$icon, $label])
                            <button
                                type="button"
                                role="radio"
                                @click="setMode(@js($value))"
                                :aria-checked="(mode === @js($value)).toString()"
                                aria-checked="{{ $mode === $value ? 'true' : 'false' }}"
                                class="app-segment"
                            >
                                <x-ui.icon :name="$icon" class="size-3.5" />
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted">
                        <span class="size-1.5 rounded-full bg-success" aria-hidden="true"></span>
                        {{ $country->is_eu_member ? __('ui.calculator.official_eu_data') : __('ui.calculator.maintained_rate_data') }}
                    </span>
                </div>

                <div @class(['grid gap-4', 'sm:grid-cols-2' => ! $compact])>
                    {{-- Country combobox --}}
                    <div class="relative" @click.outside="countryOpen = false" @keydown.escape="countryOpen = false">
                        <label id="country-label-{{ $id }}" class="mb-1.5 block text-[0.8125rem] font-semibold text-ink-muted">{{ __('ui.calculator.country_label') }}</label>
                        <button
                            type="button"
                            @click="toggleCountries()"
                            class="app-field flex h-12 items-center gap-2.5 pr-10 text-left"
                            aria-haspopup="listbox"
                            :aria-expanded="countryOpen.toString()"
                            aria-labelledby="country-label-{{ $id }} country-value-{{ $id }}"
                        >
                            <x-ui.flag :iso="$country->iso_code" :lazy="false" />
                            <span id="country-value-{{ $id }}" class="min-w-0 flex-1 truncate font-medium">{{ $country->name }}</span>
                            <span class="tabular text-sm font-semibold text-ink-muted">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</span>
                            <x-ui.icon name="chevron-down" class="pointer-events-none absolute right-3.5 bottom-4 size-4 text-ink-quiet" />
                        </button>
                        <span wire:loading wire:target="selectCountry" class="absolute right-10 bottom-4">
                            <span class="block size-4 animate-spin rounded-full border-2 border-action/30 border-t-action"></span>
                        </span>

                        <div x-cloak x-show="countryOpen" x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="-translate-y-1 opacity-0" x-transition:leave="transition duration-100 ease-out" x-transition:leave-end="opacity-0" class="app-popover absolute inset-x-0 z-30 mt-1.5 overflow-hidden">
                            <div class="border-b border-line p-2">
                                <label for="country-search-{{ $id }}" class="sr-only">{{ __('ui.calculator.search_countries') }}</label>
                                <input
                                    id="country-search-{{ $id }}"
                                    x-ref="countrySearch"
                                    x-model="countryQuery"
                                    @keydown.arrow-down.prevent="moveCountry(1)"
                                    @keydown.arrow-up.prevent="moveCountry(-1)"
                                    @keydown.enter.prevent="chooseActiveCountry()"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="{{ __('ui.calculator.search_countries') }}"
                                    class="app-field min-h-10 text-sm"
                                    role="combobox"
                                    aria-controls="country-list-{{ $id }}"
                                    aria-autocomplete="list"
                                    :aria-activedescendant="filteredCountries.length ? '{{ $id }}-country-' + countryActive : null"
                                >
                            </div>
                            <ul id="country-list-{{ $id }}" x-ref="countryList" role="listbox" aria-labelledby="country-label-{{ $id }}" class="max-h-64 overflow-y-auto overscroll-contain py-1">
                                <template x-for="(item, index) in filteredCountries" :key="item.slug">
                                    <li
                                        :id="'{{ $id }}-country-' + index"
                                        :data-index="index"
                                        role="option"
                                        :aria-selected="(item.slug === $wire.selectedCountrySlug).toString()"
                                        @click="selectCountry(item.slug)"
                                        @mousemove="countryActive = index"
                                        class="flex min-h-10 cursor-pointer items-center gap-2.5 px-3 text-sm"
                                        :class="index === countryActive ? 'bg-action-soft' : ''"
                                    >
                                        <template x-if="index === 0 || filteredCountries[index - 1].group !== item.group">
                                            <span class="sr-only" x-text="@js($groups)[item.group]"></span>
                                        </template>
                                        <img :src="'{{ asset('images/flags') }}/' + item.iso + '.svg'" alt="" width="22" height="16" class="app-flag h-4 w-[1.375rem]" loading="lazy">
                                        <span class="min-w-0 flex-1 truncate" :class="item.slug === $wire.selectedCountrySlug ? 'font-semibold text-action-deep' : 'text-ink'" x-text="item.name"></span>
                                        <span class="tabular text-xs font-semibold text-ink-muted" x-text="percent(item.rate)"></span>
                                    </li>
                                </template>
                                <li x-show="filteredCountries.length === 0" class="px-3 py-6 text-center text-sm text-ink-muted" role="presentation">{{ __('ui.calculator.no_countries_found') }}</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount-{{ $id }}" class="mb-1.5 block text-[0.8125rem] font-semibold text-ink-muted">
                            <span x-text="mode === 'include' ? @js(__('ui.calculator.amount_incl_vat')) : @js(__('ui.calculator.amount_excl_vat'))">{{ $mode === 'include' ? __('ui.calculator.amount_incl_vat') : __('ui.calculator.amount_excl_vat') }}</span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-base font-semibold text-ink-quiet" x-text="currencySymbol">{{ $currencySymbol }}</span>
                            <input
                                id="amount-{{ $id }}"
                                x-model="amount"
                                @input="persist()"
                                @keydown.enter.prevent="persist(true)"
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                value="{{ $amount }}"
                                placeholder="0.00"
                                :aria-invalid="invalid.toString()"
                                aria-describedby="amount-error-{{ $id }}"
                                class="app-field tabular h-12 pl-11 text-lg font-semibold"
                                :class="invalid && 'border-danger focus:border-danger focus:ring-danger/15'"
                            >
                        </div>
                        <p id="amount-error-{{ $id }}" x-show="invalid" x-cloak class="mt-1.5 text-xs font-medium text-danger">{{ __('ui.calculator.invalid_amount') }}</p>
                    </div>
                </div>

                @unless($compact)
                    @php($numbers = new \NumberFormatter($locale, \NumberFormatter::DECIMAL))
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3" role="group" aria-labelledby="quick-amounts-{{ $id }}">
                        <span id="quick-amounts-{{ $id }}" class="text-[0.8125rem] font-semibold text-ink-muted">{{ __('ui.calculator.quick_amounts') }}</span>
                        <div class="grid grid-cols-5 gap-1.5 sm:flex sm:flex-wrap sm:gap-2">
                        @foreach(\App\Livewire\HeroCalculator::QUICK_AMOUNTS as $quick)
                            <button
                                type="button"
                                @click="setAmount({{ $quick }})"
                                :aria-pressed="(parsedAmount === {{ $quick }}).toString()"
                                aria-pressed="{{ (float) $amount === (float) $quick ? 'true' : 'false' }}"
                                class="app-chip tabular min-h-10 justify-center px-1 sm:px-3"
                                :class="parsedAmount === {{ $quick }} && 'app-chip-active'"
                            >{{ $numbers->format($quick) }}</button>
                        @endforeach
                        </div>
                    </div>
                @endunless

                {{-- Rates --}}
                <fieldset>
                    <legend class="mb-2 text-[0.8125rem] font-semibold text-ink-muted">{{ __('ui.calculator.rate_label') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach($rates as $option)
                            <button
                                type="button"
                                wire:key="rate-{{ $country->slug }}-{{ $option['type'] }}-{{ $option['rate'] }}"
                                @click="pickRate({{ $option['rate'] }})"
                                :aria-pressed="(!useCustomRate && Number(rate) === {{ $option['rate'] }}).toString()"
                                class="app-chip min-h-10 px-3.5"
                                :class="!useCustomRate && Number(rate) === {{ $option['rate'] }} && 'app-chip-active'"
                            >
                                <span class="font-medium opacity-80">{{ $option['label'] }}</span>
                                <span class="tabular">{{ \App\Models\Country::formatRate($option['rate']) }}%</span>
                            </button>
                        @endforeach
                        <button type="button" x-show="!useCustomRate" @click="enableCustomRate()" class="app-chip min-h-10 border-dashed px-3.5">
                            <x-ui.icon name="plus" class="size-3.5" />
                            {{ __('ui.calculator.custom_percent') }}
                        </button>
                        <div x-cloak x-show="useCustomRate" class="app-chip app-chip-active min-h-10 gap-1 pr-2">
                            <label for="custom-rate-{{ $id }}">{{ __('ui.calculator.custom_label') }}</label>
                            <input id="custom-rate-{{ $id }}" x-ref="customRate" x-model="customRate" @input="persist()" type="text" inputmode="decimal" maxlength="6" placeholder="0" class="tabular w-14 rounded-xs border border-action/40 bg-surface px-1.5 py-1 text-center text-sm font-semibold text-ink outline-none focus:border-action">
                            <span>%</span>
                        </div>
                    </div>
                </fieldset>

                @unless($compact)
                    <p class="app-note mt-auto">
                        <x-ui.icon name="info" class="mt-1 size-4 text-action" />
                        <span x-text="rateDescription()">{{ __('ui.rate_type_desc.'.($rates[0]['type'] ?? 'standard')) }}</span>
                    </p>
                @endunless
            </div>

            {{-- Result --}}
            <div @class(['flex flex-col rounded-b-card border-line bg-surface-subtle p-5', 'border-t sm:p-7 md:rounded-bl-none md:rounded-tr-card md:border-t-0 md:border-l' => ! $compact, 'border-t' => $compact])>
                <p class="text-[0.8125rem] font-semibold text-ink-muted">
                    <span x-text="mode === 'include' ? @js(__('ui.calculator.net_amount')) : @js(__('ui.calculator.total_incl_vat'))">{{ $mode === 'include' ? __('ui.calculator.net_amount') : __('ui.calculator.total_incl_vat') }}</span>
                </p>
                <div class="mt-1 flex items-end justify-between gap-3">
                    <p data-calculator-result class="tabular text-4xl font-bold tracking-[-0.035em] text-ink sm:text-5xl" x-text="money(mode === 'include' ? result.net : result.gross)">{{ $money($mode === 'include' ? $calculation->net : $calculation->gross) }}</p>
                    <button type="button" @click="$copy(summaryText(), @js(__('ui.calculator.copied')))" class="app-button-secondary h-9 min-h-9 px-3 text-xs" aria-label="{{ __('ui.calculator.copy_result') }}">
                        <x-ui.icon name="copy" class="size-3.5" />
                        {{ __('ui.calculator.copy') }}
                    </button>
                </div>
                <p class="mt-2 text-sm text-ink-muted" x-text="summaryText()">{{ strtr($mode === 'include' ? __('ui.calculator.summary_remove') : __('ui.calculator.summary_add'), [':amount' => $money($calculation->input()), ':rate' => \App\Models\Country::formatRate($selectedRate).'%', ':country' => $country->name]) }}</p>

                <dl class="mt-5 divide-y divide-line rounded-control border border-line bg-surface">
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-sm text-ink-muted">{{ __('ui.calculator.net_amount') }}</dt>
                        <dd class="tabular text-sm font-semibold text-ink" x-text="money(result.net)">{{ $money($calculation->net) }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-sm text-ink-muted">
                            <span x-text="@js(__('ui.calculator.vat_short')) + ' ' + percent(effectiveRate)">{{ __('ui.calculator.vat_short') }} {{ \App\Models\Country::formatRate($selectedRate) }}%</span>
                        </dt>
                        <dd class="tabular text-sm font-semibold text-action-deep" x-text="(mode === 'include' ? '− ' : '+ ') + money(result.vat)">{{ ($mode === 'include' ? '− ' : '+ ').$money($calculation->vat) }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-sm font-semibold text-ink">{{ __('ui.calculator.gross_amount') }}</dt>
                        <dd class="tabular text-sm font-bold text-ink" x-text="money(result.gross)">{{ $money($calculation->gross) }}</dd>
                    </div>
                </dl>

                @unless($compact)
                    <div class="mt-4" aria-hidden="true">
                        <div class="flex h-1.5 overflow-hidden rounded-xs bg-action/15">
                            <div class="h-full bg-action transition-[width] duration-300 ease-out-quint" :style="'width:' + netShare + '%'" style="width: {{ $calculation->gross > 0 ? round($calculation->net / $calculation->gross * 100, 1) : 100 }}%"></div>
                        </div>
                        <div class="mt-1.5 flex justify-between text-xs text-ink-muted">
                            <span x-text="@js(__('ui.calculator.net_short')) + ' ' + netShare + '%'"></span>
                            <span x-text="@js(__('ui.calculator.vat_short')) + ' ' + (Math.round((100 - netShare) * 10) / 10) + '%'"></span>
                        </div>
                    </div>
                @endunless

                <div class="mt-auto flex flex-wrap gap-2 pt-5">
                    <a :href="shareUrl(@js(url(locale_path('/vat-calculation'))), $wire.selectedCountrySlug)" href="{{ url(\App\Livewire\SharedCalculation::calculationUrl($country->slug, $calculation->input(), $customRate ?? $selectedRate, $mode)) }}" @if($embedded) target="_blank" rel="noopener" @endif class="app-button-primary h-10 min-h-10 flex-1 whitespace-nowrap px-4">
                        <x-ui.icon name="share" class="size-4" />
                        {{ __('ui.calculator.share_details') }}
                    </a>
                    @unless(in_array($surface, ['workspace', 'country-image', 'embed'], true) || $compact)
                        <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" x-bind:href="@js(locale_path('/vat-calculator')) + '/' + $wire.selectedCountrySlug" class="app-button-secondary h-10 min-h-10 flex-1 whitespace-nowrap px-4">
                            {{ __('ui.calculator.full_calculator') }}
                            <x-ui.icon name="arrow-right" class="size-4" />
                        </a>
                    @endunless
                </div>
                <p class="sr-only" aria-live="polite" x-text="announcement"></p>
            </div>
        </div>

        @unless($compact || $embedded)
            <x-rate-freshness :updated="$country->updated_at" :eu="$country->is_eu_member" :on-dark="$onDark" class="mx-auto mt-4 max-w-5xl justify-center">
                <span class="inline-flex items-center gap-1.5">
                    <x-ui.icon name="zap" class="size-3.5 shrink-0" />
                    {{ __('ui.calculator.instant_results') }}
                </span>
            </x-rate-freshness>

            <section x-cloak x-show="$wire.history.length > 0" class="mx-auto mt-8 max-w-5xl" aria-labelledby="history-heading-{{ $id }}">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="history-heading-{{ $id }}" @class(['inline-flex items-center gap-2 text-sm font-semibold', 'text-white' => $onDark, 'text-ink' => ! $onDark])>
                        <x-ui.icon name="history" class="size-4" />
                        {{ __('ui.calculator.recent_calculations') }}
                    </h2>
                    <button type="button" wire:click="clearHistory" @class(['rounded-xs px-2 py-1 text-xs font-semibold', 'text-white/75 hover:bg-white/10 hover:text-white' => $onDark, 'text-action hover:bg-action-soft' => ! $onDark])>{{ __('ui.calculator.clear_all') }}</button>
                </div>
                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="entry in $wire.history" :key="entry.key">
                        <button type="button" @click="restore(entry)" class="app-surface pressable group flex items-center gap-3 p-3 text-left hover:border-action/50">
                            <img :src="'{{ asset('images/flags') }}/' + entry.iso + '.svg'" alt="" width="28" height="21" class="app-flag h-[1.3125rem] w-7" loading="lazy">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-ink" x-text="entry.country"></span>
                                <span class="tabular block text-xs text-ink-muted" x-text="money(entry.amount, entry.currency) + (entry.mode === 'include' ? ' − ' : ' + ') + percent(entry.rate)"></span>
                            </span>
                            <span class="tabular text-sm font-bold text-ink" x-text="money(entry.mode === 'include' ? entry.net : entry.gross, entry.currency)"></span>
                        </button>
                    </template>
                </div>
            </section>
        @endunless
    @endif
</div>
