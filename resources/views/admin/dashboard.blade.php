<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Business Overview</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="flex gap-4 text-sm font-semibold">
                <a href="{{ route('admin.affiliates.index') }}" class="text-vale-red hover:text-red-600">Manage Affiliates &rarr;</a>
                <a href="{{ route('admin.discount-codes.index') }}" class="text-vale-red hover:text-red-600">Manage Discount Codes &rarr;</a>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Revenue</h3>
                <div class="grid sm:grid-cols-4 gap-4">
                    @php
                        $tiles = [
                            'Revenue (inc VAT)' => '£'.number_format($metrics['revenue'], 2),
                            'Revenue (ex VAT)' => '£'.number_format($metrics['revenue_ex_vat'], 2),
                            'Total variable costs' => '£'.number_format($metrics['total_costs'], 2),
                            'Contribution margin' => '£'.number_format($metrics['contribution_margin'], 2).' ('.number_format($metrics['contribution_margin_pct'], 1).'%)',
                        ];
                    @endphp
                    @foreach ($tiles as $label => $value)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $label }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Costs</h3>
                <div class="grid sm:grid-cols-4 gap-4">
                    @php
                        $tiles = [
                            'API spend (vehicle data)' => '£'.number_format($metrics['api_spend'], 2),
                            'AI spend' => '£'.number_format($metrics['ai_spend'], 2),
                            'Avg cost / ValeCheck' => '£'.number_format($metrics['avg_cost_per_check'], 2),
                            'Avg cost / Plus' => '£'.number_format($metrics['avg_cost_per_plus'], 2),
                            'Avg cost / Rebuild' => '£'.number_format($metrics['avg_cost_per_rebuild'], 2),
                        ];
                    @endphp
                    @foreach ($tiles as $label => $value)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $label }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Activity</h3>
                <div class="grid sm:grid-cols-4 gap-4">
                    @php
                        $tiles = [
                            'Users' => $metrics['users_count'],
                            'ValeCheck completed' => $metrics['checks_completed'],
                            'Plus completed' => $metrics['plus_completed'],
                            'Rebuild completed' => $metrics['rebuild_completed'],
                            'Active subscriptions' => $metrics['active_subscriptions'],
                            'Failed checks' => $metrics['checks_failed'],
                            'Failed AI calls' => $metrics['failed_ai_calls'],
                        ];
                    @endphp
                    @foreach ($tiles as $label => $value)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $label }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Affiliates</h3>
                <div class="grid sm:grid-cols-3 gap-4">
                    @php
                        $tiles = [
                            'Affiliates' => $metrics['affiliates_count'],
                            'Referrals' => $metrics['referrals_count'],
                            'Commissions owed' => '£'.number_format($metrics['commissions_total'], 2),
                        ];
                    @endphp
                    @foreach ($tiles as $label => $value)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $label }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Listing Import</h3>
                <div class="grid sm:grid-cols-4 gap-4">
                    @php
                        $listingImport = $metrics['listing_import'];
                        $tiles = [
                            'Import attempts' => $listingImport['total_attempts'],
                            'Successful' => $listingImport['successful'],
                            'Partial' => $listingImport['partial'],
                            'Failed / blocked' => $listingImport['failed'] + $listingImport['blocked'],
                            'Avg duration' => $listingImport['avg_duration_ms'].'ms',
                            'Avg images found' => $listingImport['avg_images_found'],
                        ];
                    @endphp
                    @foreach ($tiles as $label => $value)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $label }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                @if (! empty($listingImport['by_provider']))
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mt-4">
                        <p class="text-xs uppercase tracking-widest text-gray-400 mb-2">By provider</p>
                        <table class="w-full text-sm">
                            @foreach ($listingImport['by_provider'] as $provider => $stats)
                                <tr class="border-t border-gray-100 first:border-t-0">
                                    <td class="py-1.5 text-vale-navy capitalize">{{ $provider }}</td>
                                    <td class="py-1.5 text-gray-500 text-right">{{ $stats['success'] }} / {{ $stats['total'] }} succeeded</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
