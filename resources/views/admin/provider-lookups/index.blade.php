<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Provider Lookups</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500">
                Every One Auto API call, most recent first — one row per call, so the count of rows for a given
                registration is the real number of calls that report made.
            </p>

            <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm overflow-x-auto">
                <div class="grid grid-cols-7 gap-4 px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-400">
                    <span>Timestamp</span>
                    <span>Provider</span>
                    <span>Endpoint</span>
                    <span>Registration</span>
                    <span>Status</span>
                    <span>HTTP</span>
                    <span>Report</span>
                </div>
                @forelse ($logs as $log)
                    <div class="grid grid-cols-7 gap-4 px-4 py-3 text-sm items-center">
                        <span class="text-gray-500">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                        <span class="text-vale-navy">{{ $log->provider }}</span>
                        <span class="text-vale-navy font-mono text-xs">{{ $log->endpoint }}</span>
                        <span class="text-vale-navy font-mono">{{ $log->registration }}</span>
                        <span>
                            @if ($log->status === \App\Models\ProviderLookupLog::STATUS_SUCCESS)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700">Success</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-vale-red" title="{{ $log->error_message }}">Failed</span>
                            @endif
                        </span>
                        <span class="text-gray-500">{{ $log->http_status ?? '—' }}</span>
                        <span class="text-gray-500 capitalize">{{ $log->vehicleCheck?->status ?? '—' }}</span>
                    </div>
                @empty
                    <p class="p-4 text-gray-400 text-sm">No provider lookups yet.</p>
                @endforelse
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
