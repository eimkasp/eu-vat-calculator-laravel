<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-transparent">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', __('ui.site_name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-0 bg-transparent text-ink antialiased">
    <main>
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>
    <x-toasts />
    @livewireScripts
    @if (config('app.data_domain') && app()->isProduction())
        <script defer data-cfasync="false" data-domain="{{ config('app.data_domain') }}" src="{{ config('app.plausible_script') }}"></script>
    @endif
</body>
</html>
