<x-app-layout>
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <h1 class="font-display font-bold text-2xl text-vale-navy">Payment coming shortly</h1>
        <p class="text-gray-500 mt-3">
            Vehicle check #{{ $vehicleCheck->id }} for {{ $vehicleCheck->registration }} is waiting on payment.
            Stripe hasn't been configured yet — add STRIPE_KEY and STRIPE_SECRET to .env to enable checkout.
        </p>
    </div>
</x-app-layout>
