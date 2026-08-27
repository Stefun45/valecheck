<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page not found — {{ config('app.name', 'ValeCheck') }}</title>
        @include('partials.favicons')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-vale-navy">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
            <x-application-logo text-class="text-xl" class="mb-8" />
            <h1 class="font-display font-bold text-3xl text-vale-navy">Page not found</h1>
            <p class="text-gray-500 mt-3 max-w-sm">
                We couldn't find that page — it may have been moved, or the link might be out of date.
            </p>
            <a href="{{ url('/') }}" class="inline-flex items-center mt-6 px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                Back to ValeCheck
            </a>
        </div>
    </body>
</html>
