<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — New Affiliate</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm text-gray-500 mb-4">The affiliate must already have a ValeCheck account — enter the email they registered with.</p>

                <form method="POST" action="{{ route('admin.affiliates.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="email" value="Affiliate's account email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" value="{{ old('email') }}" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Display name" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" value="{{ old('name') }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="referral_code" value="Referral code (optional — auto-generated if left blank)" />
                        <x-text-input id="referral_code" name="referral_code" class="block mt-1 w-full uppercase" value="{{ old('referral_code') }}" placeholder="JOHN10" />
                        <x-input-error :messages="$errors->get('referral_code')" class="mt-2" />
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.affiliates.index') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Create Affiliate</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
