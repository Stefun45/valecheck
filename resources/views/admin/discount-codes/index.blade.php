<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Discount Codes</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500">All Discount Codes</h3>
                <a href="{{ route('admin.discount-codes.create') }}" class="inline-flex items-center px-4 py-2 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
                    New Code
                </a>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="p-4">Code</th>
                            <th class="p-4">Discount</th>
                            <th class="p-4">Applies To</th>
                            <th class="p-4">Used</th>
                            <th class="p-4">Expires</th>
                            <th class="p-4">Status</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discountCodes as $code)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="p-4 font-mono text-vale-navy font-semibold">{{ $code->code }}</td>
                                <td class="p-4 text-vale-navy">{{ $code->type === 'percentage' ? number_format($code->value, 0).'%' : '£'.number_format($code->value, 2) }}</td>
                                <td class="p-4 text-vale-navy">{{ $code->applicable_products ? implode(', ', array_map('ucfirst', $code->applicable_products)) : 'All products' }}</td>
                                <td class="p-4 text-vale-navy">{{ $code->times_redeemed }}{{ $code->max_redemptions ? ' / '.$code->max_redemptions : '' }}</td>
                                <td class="p-4 text-vale-navy">{{ $code->expires_at?->format('d M Y') ?? 'Never' }}</td>
                                <td class="p-4">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $code->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $code->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.discount-codes.edit', $code) }}" class="text-gray-500 hover:text-vale-navy">Edit</a>
                                    <form method="POST" action="{{ route('admin.discount-codes.toggle', $code) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ml-3 text-gray-500 hover:text-vale-navy">{{ $code->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-gray-400 text-sm">No discount codes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
