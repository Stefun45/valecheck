{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again. --}}
<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="calendar" />MOT &amp; Mileage</h3>
    @if ($history?->mot_history)
        <div x-data="{ open: null }">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-400 text-left">
                        <th class="font-normal pb-2">Test Date</th>
                        <th class="font-normal pb-2">Result</th>
                        <th class="font-normal pb-2">Mileage</th>
                        <th class="font-normal pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_reverse($history->mot_history) as $i => $test)
                        @php $advisories = $test['advisories'] ?? []; @endphp
                        <tr
                            class="border-t border-gray-100 {{ $advisories ? 'cursor-pointer hover:bg-gray-50' : '' }}"
                            @if ($advisories) @click="open = open === {{ $i }} ? null : {{ $i }}" @endif
                        >
                            <td class="py-2 text-vale-navy">{{ isset($test['test_date']) ? \Illuminate\Support\Carbon::parse($test['test_date'])->format('d M Y') : '—' }}</td>
                            <td class="py-2">
                                @if (isset($test['result']))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold capitalize {{ strtolower($test['result']) === 'fail' ? 'bg-red-50 text-vale-red' : 'bg-green-50 text-green-700' }}">{{ $test['result'] }}</span>
                                @else
                                    <span class="text-vale-navy">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-vale-navy">{{ isset($test['mileage']) ? number_format($test['mileage']).' mi' : '—' }}</td>
                            <td class="py-2 text-right">
                                @if ($advisories)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-vale-red">
                                        {{ count($advisories) }} advisor{{ count($advisories) === 1 ? 'y' : 'ies' }}
                                        <svg :class="open === {{ $i }} ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if ($advisories)
                            <tr x-show="open === {{ $i }}">
                                <td colspan="4" class="pb-3 pt-0">
                                    <ul class="list-disc list-inside space-y-1 text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
                                        @foreach ($advisories as $advisory)
                                            <li>{{ $advisory }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($history->mileage_anomaly)
            <p class="text-vale-red text-sm font-semibold mt-3">Mileage anomaly detected in the MOT history.</p>
        @endif
    @else
        <p class="text-vale-navy">No MOT history available.</p>
    @endif
</div>
