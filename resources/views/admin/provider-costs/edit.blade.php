<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Provider Costs</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm text-gray-500 mb-6">
                    The real net cost of one call to each One Auto endpoint, used to work out margin in Admin
                    Metrics. Changing a figure here only affects calls logged from now on — every call already made
                    keeps the cost that was actually in effect when it happened, so editing these never rewrites
                    past reporting.
                </p>

                <form method="POST" action="{{ route('admin.provider-costs.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @foreach (\App\Models\ProviderEndpointCost::ENDPOINTS as $endpoint => $label)
                        @php($field = \App\Models\ProviderEndpointCost::fieldName($endpoint))
                        <div>
                            <x-input-label for="{{ $field }}" :value="$label" />
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $endpoint }}</p>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                <x-text-input :id="$field" :name="$field" type="number" step="0.0001" min="0" class="block w-full pl-7" value="{{ old($field, $costs[$endpoint] ?? '') }}" required />
                            </div>
                            <x-input-error :messages="$errors->get($field)" class="mt-2" />
                        </div>
                    @endforeach

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save Costs</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
