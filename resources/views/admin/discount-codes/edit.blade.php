<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Edit Discount Code</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-xs uppercase tracking-widest text-gray-400 mb-4">Code: <span class="font-mono text-vale-navy">{{ $discountCode->code }}</span> (not editable)</p>

                <form method="POST" action="{{ route('admin.discount-codes.update', $discountCode) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @include('admin.discount-codes.partials.fields')

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.discount-codes.index') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
