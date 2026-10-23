@php($inputId = 'vat-change-email-'.$this->getId())

<section @class(['app-surface relative overflow-hidden p-5', 'sm:p-6' => ! $compact]) aria-labelledby="{{ $inputId }}-title">
    <div @class(['flex flex-col gap-5', 'lg:flex-row lg:items-center lg:justify-between lg:gap-8' => ! $compact])>
        <div class="flex gap-4">
            <span class="hidden size-11 shrink-0 items-center justify-center rounded-control bg-action-soft text-action sm:flex" aria-hidden="true">
                <x-ui.icon name="news" class="size-5" />
            </span>
            <div>
                <h2 id="{{ $inputId }}-title" @class(['font-semibold text-ink', 'text-lg' => $compact, 'text-xl' => ! $compact])>{{ __('ui.alerts.title') }}</h2>
                <p class="mt-1.5 max-w-[60ch] text-sm leading-6 text-ink-muted">{{ __('ui.alerts.description') }}</p>
            </div>
        </div>

        <form wire:submit="subscribe" @class(['w-full', 'lg:max-w-md' => ! $compact]) aria-label="{{ __('ui.alerts.eyebrow') }}" novalidate>
            <div @class(['flex flex-col gap-2.5', 'sm:flex-row' => ! $compact])>
                <label for="{{ $inputId }}" class="sr-only">{{ __('ui.alerts.email_label') }}</label>
                <input
                    id="{{ $inputId }}"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    inputmode="email"
                    placeholder="you@example.com"
                    class="app-field h-11 min-w-0 flex-1"
                    @error('email') aria-invalid="true" aria-describedby="{{ $inputId }}-error" @enderror
                >
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <button type="submit" class="app-button-primary h-11 shrink-0" wire:loading.attr="disabled" wire:target="subscribe">
                    <span wire:loading.remove wire:target="subscribe">{{ __('ui.alerts.subscribe') }}</span>
                    <span wire:loading wire:target="subscribe">{{ __('ui.alerts.subscribing') }}</span>
                </button>
            </div>

            @error('email')
                <p id="{{ $inputId }}-error" class="mt-2 text-sm font-medium text-danger" role="alert">{{ $message }}</p>
            @enderror

            @if($statusMessage)
                <p @class(['mt-2 flex items-start gap-1.5 text-sm font-medium', 'text-success' => $messageType === 'success', 'text-danger' => $messageType !== 'success']) role="status">
                    <x-ui.icon :name="$messageType === 'success' ? 'check-circle' : 'alert-triangle'" class="mt-0.5 size-4" />
                    {{ $statusMessage }}
                </p>
            @else
                <p class="mt-2 text-xs text-ink-muted">{{ __('ui.alerts.privacy') }}</p>
            @endif
        </form>
    </div>
</section>
