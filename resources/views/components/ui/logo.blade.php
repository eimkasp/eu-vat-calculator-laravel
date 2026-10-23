@php($size = preg_match('/(^|\s)size-/', (string) $attributes->get('class')) ? '' : 'size-8 ')
<svg {{ $attributes->merge(['class' => $size.'shrink-0']) }} viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path class="fill-ink" fill="#111825" d="M8 0h18l6 6v18a8 8 0 0 1-8 8H6l-6-6V8a8 8 0 0 1 8-8Z"/>
    <path class="stroke-workspace" stroke="#f8fbfe" d="M10.5 21.5 21.5 10.5" stroke-width="2.75" stroke-linecap="round" fill="none"/>
    <circle class="fill-workspace" fill="#f8fbfe" cx="11.25" cy="11.25" r="3.25"/>
    <circle class="fill-button" fill="#0462d3" cx="20.75" cy="20.75" r="3.25"/>
</svg>
