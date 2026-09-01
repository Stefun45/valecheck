<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Affiliates</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500">All Affiliates</h3>
                <a href="{{ route('admin.affiliates.create') }}" class="inline-flex items-center px-4 py-2 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
                    New Affiliate
                </a>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="p-4">Name</th>
                            <th class="p-4">Code</th>
                            <th class="p-4">Referrals</th>
                            <th class="p-4">Pending Commission</th>
                            <th class="p-4">Paid Commission</th>
                            <th class="p-4">Status</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creators as $creator)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="p-4 text-vale-navy font-semibold">{{ $creator->name }}</td>
                                <td class="p-4 font-mono text-vale-navy">{{ $creator->referral_code }}</td>
                                <td class="p-4 text-vale-navy">{{ $creator->referrals_count }}</td>
                                <td class="p-4 text-vale-navy">£{{ number_format($creator->pending_total, 2) }}</td>
                                <td class="p-4 text-vale-navy">£{{ number_format($creator->paid_total, 2) }}</td>
                                <td class="p-4">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $creator->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $creator->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.affiliates.show', $creator) }}" class="text-vale-red font-semibold hover:text-red-600">View</a>
                                    <a href="{{ route('admin.affiliates.edit', $creator) }}" class="ml-3 text-gray-500 hover:text-vale-navy">Edit</a>
                                    <form method="POST" action="{{ route('admin.affiliates.toggle', $creator) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ml-3 text-gray-500 hover:text-vale-navy">{{ $creator->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-gray-400 text-sm">No affiliates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
