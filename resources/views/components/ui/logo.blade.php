@php($size = preg_match('/(^|\s)size-/', (string) $attributes->get('class')) ? '' : 'size-8 ')
<svg {{ $attributes->merge(['class' => $size.'shrink-0']) }} viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <rect width="32" height="32" rx="6" fill="#fff"/>
    <path d="M10.5 21.5 21.5 10.5" stroke="#0a2a7a" stroke-width="2.75" stroke-linecap="round"/>
    <circle cx="11.25" cy="11.25" r="3.25" fill="#0a2a7a"/>
    <circle cx="20.75" cy="20.75" r="3.25" fill="#ffcc00"/>
</svg>
