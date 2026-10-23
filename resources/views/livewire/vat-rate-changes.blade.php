@use('App\Models\Country')

<section class="app-surface p-5" aria-labelledby="rate-changes-heading">
    <div class="flex items-center justify-between gap-3">
        <h2 id="rate-changes-heading" class="text-base font-bold text-ink">{{ __('ui.rate_changes.title') }}</h2>
        <a href="{{ locale_path('/vat-changes') }}" class="text-xs font-semibold text-action hover:text-action-deep">{{ __('ui.rate_changes.full_history') }}</a>
    </div>

    @foreach([['upcoming', $upcoming, __('ui.rate_changes.upcoming')], ['recent', $recent, __('ui.rate_changes.recent')]] as [$key, $changes, $label])
        @if($changes->isNotEmpty())
            <h3 class="mt-4 font-display text-[0.8125rem] font-medium text-ink-muted">{{ $label }}</h3>
            <ol class="mt-2 space-y-1">
                @foreach($changes as $change)
                    @php($up = (float) $change->new_rate > (float) $change->old_rate)
                    <li>
                        <a href="{{ locale_path('/vat-calculator/'.$change->country?->slug) }}" class="flex items-center gap-3 rounded-control px-2 py-2 transition-colors hover:bg-surface-subtle">
                            <x-ui.flag :iso="$change->country?->iso_code" size="lg" class="h-5 w-[1.625rem]" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-ink">{{ $change->country?->name }}</span>
                                <span class="flex flex-wrap gap-x-2 text-xs text-ink-muted">
                                    <span>{{ __('ui.rate_type.'.$change->rate_type) }}</span>
                                    <span class="tabular">{{ $change->change_date?->translatedFormat('M Y') }}</span>
                                </span>
                            </span>
                            <span class="text-right">
                                <span class="tabular block text-sm font-bold text-ink">{{ Country::formatRate($change->new_rate) }}%</span>
                                <span @class(['tabular inline-flex items-center gap-0.5 text-xs font-semibold', 'text-danger' => $up, 'text-success' => ! $up])>
                                    <x-ui.icon :name="$up ? 'trending-up' : 'trending-down'" class="size-3" />
                                    <span class="sr-only">{{ $up ? __('ui.rate_changes.increase') : __('ui.rate_changes.decrease') }}</span>
                                    {{ __('ui.rate_changes.points', ['value' => ($up ? '+' : '−').Country::formatRate(abs((float) $change->new_rate - (float) $change->old_rate))]) }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    @endforeach

    @if($upcoming->isEmpty() && $recent->isEmpty())
        <p class="mt-4 rounded-card bg-surface-subtle px-4 py-6 text-center text-sm text-ink-muted">{{ __('ui.rate_changes.no_changes') }}</p>
    @endif
</section>
