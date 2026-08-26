<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — New Discount Code</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.discount-codes.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="code" value="Code (optional — auto-generated if left blank)" />
                        <x-text-input id="code" name="code" class="block mt-1 w-full uppercase" value="{{ old('code') }}" placeholder="SAVE10" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    @include('admin.discount-codes.partials.fields')

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.discount-codes.index') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Create Code</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
