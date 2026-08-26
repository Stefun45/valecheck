<div class="text-center max-w-md mx-auto">
    <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-red-50 text-vale-red text-2xl font-bold mb-6 border border-red-100">!</div>
    <h1 class="font-display font-bold text-2xl text-vale-navy">We couldn't complete this report</h1>
    <p class="text-gray-500 mt-3">Something went wrong while processing this check. You have not been charged, and any credit used has been refunded to your account.</p>
    <a href="{{ route('vehicle-checks.start') }}" wire:navigate class="inline-block mt-6 text-sm font-semibold text-vale-red hover:text-red-600">Try another check &rarr;</a>
</div>
