<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin - Trader Verifications</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-gray-500">
                Business details submitted by Trader/Dealer subscribers before trade-restricted report content
                (e.g. high-risk markers) unlocks for them - see User::hasVerifiedTradeAccess(). Pending first.
            </p>

            <div class="space-y-4">
                @forelse ($verifications as $verification)
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
                            <div>
                                <p class="text-vale-navy font-semibold">{{ $verification->company_name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Company no. {{ $verification->company_number }}
                                    @if ($verification->vat_number)
                                        &middot; VAT {{ $verification->vat_number }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $verification->user->name }} ({{ $verification->user->email }})</p>
                                <p class="text-xs text-gray-400 mt-0.5">Submitted {{ $verification->submitted_at->format('d M Y H:i') }}</p>
                                @if ($verification->notes)
                                    <p class="text-xs text-gray-500 mt-1">Note: {{ $verification->notes }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shrink-0
                                {{ match ($verification->status) {
                                    \App\Models\TraderVerification::STATUS_APPROVED => 'bg-green-50 text-green-700',
                                    \App\Models\TraderVerification::STATUS_REJECTED => 'bg-red-50 text-vale-red',
                                    default => 'bg-yellow-50 text-yellow-700',
                                } }}">
                                {{ ucfirst($verification->status) }}
                            </span>
                        </div>

                        @if ($verification->status === \App\Models\TraderVerification::STATUS_PENDING)
                            <div class="flex flex-col sm:flex-row gap-3 mt-4 pt-4 border-t border-gray-100">
                                <form method="POST" action="{{ route('admin.trader-verifications.approve', $verification) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-vale-navy hover:bg-vale-navy/90 rounded-full font-semibold text-sm text-white">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.trader-verifications.reject', $verification) }}" class="flex-1 flex flex-col sm:flex-row gap-2">
                                    @csrf
                                    <input type="text" name="notes" placeholder="Reason for rejection (shown to the trader)" class="flex-1 rounded-md border-gray-300 text-sm" required>
                                    <button type="submit" class="px-4 py-2 border-2 border-vale-red text-vale-red hover:bg-red-50 rounded-full font-semibold text-sm">Reject</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">No trader verifications submitted yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
