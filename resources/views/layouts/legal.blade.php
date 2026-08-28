<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name', 'ValeCheck') }}</title>
        @include('partials.favicons')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-vale-navy">
        <header class="border-b border-gray-200">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
                <a href="{{ url('/') }}" wire:navigate>
                    <x-application-logo text-class="text-lg" />
                </a>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="font-display font-bold text-3xl text-vale-navy mb-2">{{ $title }}</h1>
            <p class="text-sm text-gray-400 mb-10">Last updated {{ $lastUpdated ?? now()->format('d M Y') }}</p>

            <div class="space-y-6">
                @yield('content')
            </div>
        </main>

        @include('partials.footer')
    </body>
</html>
