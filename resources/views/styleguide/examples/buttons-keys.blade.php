<ul class="flex flex-wrap items-end gap-6" aria-label="Key edges">
    @foreach([
        ['shadow-key-accent', 'Primary key', 'border-button-edge bg-button shadow-key-accent'],
        ['shadow-key', 'Secondary key and chip', 'border-line-strong bg-key-face shadow-key'],
        ['shadow-key-press', 'Pressed in', 'translate-y-0.5 border-action bg-action-soft shadow-key-press'],
    ] as [$token, $label, $classes])
        <li class="flex min-w-32 flex-col gap-2.5">
            <span class="block h-11 w-32 rounded-control border {{ $classes }}" aria-hidden="true"></span>
            <span>
                <code class="block font-mono text-xs text-ink">{{ $token }}</code>
                <span class="block font-display text-[0.8125rem] font-medium text-ink-muted">{{ $label }}</span>
            </span>
        </li>
    @endforeach
    <li class="flex min-w-32 flex-col gap-2.5">
        <span class="flex h-11 items-center gap-1"><kbd class="app-kbd">⌘</kbd><kbd class="app-kbd">K</kbd></span>
        <span>
            <code class="block font-mono text-xs text-ink">app-kbd</code>
            <span class="block font-display text-[0.8125rem] font-medium text-ink-muted">Keyboard hint</span>
        </span>
    </li>
</ul>
