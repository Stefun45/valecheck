<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Coming Soon — {{ config('app.name', 'ValeCheck') }}</title>
        @include('partials.favicons')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-vale-navy">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
            <x-application-logo text-class="text-xl" class="mb-8" />
            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-vale-navy/70 border border-gray-200 rounded-full px-4 py-1.5 bg-white mb-6">
                <span class="h-1.5 w-1.5 rounded-full bg-vale-red"></span>
                Coming Soon
            </span>
            <h1 class="font-display font-bold text-3xl text-vale-navy">We're putting the finishing touches on ValeCheck</h1>
            <p class="text-gray-500 mt-3 max-w-sm">
                UK vehicle history and value reports — launching soon. Already have an account?
            </p>
            <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center mt-6 px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                Log In
            </a>
        </div>
        @include('partials.footer')
    </body>
</html>
