<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="font-display font-bold text-2xl text-vale-navy">My Reports</h1>
        <p class="text-gray-500 mt-1">Every check you've run, with access to the report and its PDF for {{ config('valecheck.reports.retention_days') }} days after completion.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm">
        @forelse ($checks as $check)
            <div class="flex justify-between items-center p-4">
                <div>
                    <p class="text-vale-navy font-mono">{{ $check->registration }}</p>
                    <p class="text-xs text-gray-400">{{ $check->vehicle?->description() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $check->created_at->format('d M Y') }}</p>
                </div>
                <div class="text-right flex items-center gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-gray-400">{{ ucfirst($check->type) }}</p>
                        @if ($check->isPurged())
                            <p class="text-sm font-semibold text-gray-400">Expired</p>
                        @else
                            <p class="text-sm font-semibold capitalize {{ $check->status === 'failed' ? 'text-vale-red' : 'text-vale-navy' }}">{{ $check->status }}</p>
                        @endif
                    </div>

                    @if ($check->isPurged())
                        <span class="text-xs text-gray-400 italic">Data no longer available</span>
                    @else
                        <div class="flex flex-col items-end gap-1">
                            <a href="{{ route('vehicle-checks.show', $check) }}" wire:navigate class="text-sm font-semibold text-vale-red hover:text-red-600">View</a>
                            @if ($check->status === \App\Models\VehicleCheck::STATUS_COMPLETED)
                                <a href="{{ route('vehicle-checks.pdf', $check) }}" class="text-xs text-gray-500 hover:text-vale-navy">Download PDF</a>
                                @if ($check->expires_at)
                                    <span class="text-[10px] text-gray-400">Available until {{ $check->expires_at->format('d M Y') }}</span>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-gray-400 text-sm">No checks yet.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $checks->links() }}
    </div>
</div>
