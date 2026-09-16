<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Users</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-3">
                <x-text-input type="search" name="search" value="{{ $search }}" placeholder="Search by name or email" class="block w-full max-w-sm" />
                <x-primary-button type="submit">Search</x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-vale-navy">Clear</a>
                @endif
            </form>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="p-4">Name</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Checks</th>
                            <th class="p-4">Custom Pricing</th>
                            <th class="p-4">Joined</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="p-4 text-vale-navy font-semibold">{{ $user->name }}{{ $user->is_admin ? ' (admin)' : '' }}</td>
                                <td class="p-4 text-gray-500">{{ $user->email }}</td>
                                <td class="p-4 text-vale-navy">{{ $user->vehicle_checks_count }}</td>
                                <td class="p-4">
                                    @if ($user->productPriceOverrides->isNotEmpty())
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-700">
                                            {{ $user->productPriceOverrides->pluck('type')->map(fn ($t) => ucfirst($t))->join(', ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">Standard</span>
                                    @endif
                                </td>
                                <td class="p-4 text-gray-500">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="p-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.users.edit-prices', $user) }}" class="text-vale-red hover:text-red-600 font-semibold">Set Prices</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-gray-400 text-sm">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
