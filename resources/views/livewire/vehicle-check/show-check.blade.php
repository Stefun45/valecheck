<div @if($this->isProcessing()) wire:poll.2s @endif class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-vale-red rounded-xl p-4 text-sm mb-4">{{ session('error') }}</div>
    @endif

    @if ($vehicleCheck->status === \App\Models\VehicleCheck::STATUS_COMPLETED && ! $vehicleCheck->isPurged())
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <a href="{{ route('vehicle-checks.pdf', $vehicleCheck) }}" class="inline-flex items-center px-4 py-2 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy hover:bg-gray-50 transition">
                Download PDF
            </a>
            @if ($vehicleCheck->expires_at)
                <p class="text-xs text-gray-400">Available until {{ $vehicleCheck->expires_at->format('d M Y') }}.</p>
            @endif
        </div>
    @endif

    @include($contentView, ['check' => $vehicleCheck])
</div>
