<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — {{ $creator->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-xs uppercase tracking-widest text-gray-400">Referral code</p>
                <p class="font-mono text-2xl font-bold text-vale-navy mt-1">{{ $creator->referral_code }}</p>
                <p class="text-sm text-gray-500 mt-2">Account: {{ $creator->user?->email }}</p>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Referrals ({{ $creator->referrals->count() }})</h3>
                <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm">
                    @forelse ($creator->referrals as $referral)
                        <div class="p-4 flex justify-between items-center">
                            <span class="text-vale-navy">{{ $referral->referredUser?->email }}</span>
                            <span class="text-xs text-gray-400">{{ $referral->attributed_at->format('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="p-4 text-gray-400 text-sm">No referrals yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Commissions ({{ $creator->commissions->count() }})</h3>
                <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm">
                    @forelse ($creator->commissions as $commission)
                        <div class="p-4 flex justify-between items-center">
                            <div>
                                <p class="text-vale-navy font-semibold">£{{ number_format($commission->amount, 2) }}</p>
                                <p class="text-xs text-gray-400">{{ ucfirst($commission->type) }} · {{ $commission->payment?->description }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $commission->status === 'paid' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($commission->status) }}
                                </span>
                                @if ($commission->status !== 'paid' && $commission->status !== 'reversed')
                                    <form method="POST" action="{{ route('admin.commissions.mark-paid', $commission) }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-vale-red font-semibold hover:text-red-600">Mark Paid</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="p-4 text-gray-400 text-sm">No commissions yet.</p>
                    @endforelse
                </div>
            </div>

            <a href="{{ route('admin.affiliates.index') }}" class="text-sm text-gray-500 hover:text-vale-navy">&larr; Back to affiliates</a>
        </div>
    </div>
</x-app-layout>
