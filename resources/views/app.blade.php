<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('') }}">
    <meta name="environment" content="{{ config('app.env') }}">
    <title inertia>Kontakami {{ @$pageTitle ? "| {$pageTitle}" : '' }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="stylesheet" href="https://muhammadlailil.github.io/iconsax/style/iconsax.css" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @if (config('app.env') === 'development')
        @routes
    @endif
    @vite('resources/css/app.css')

    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="min-h-screen bg-body font-krub-medium text-[#333030]" x-data="{ sidebarCollapse: false }"
    x-bind:class="sidebarCollapse ? 'sidebar-collapse' : 'sidebar-hide'">

    @inertia
</body>

</html>
