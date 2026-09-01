<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">
            {{ __('Enterprise enquiry') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-gray-500 text-sm mb-6">Tell us about your volume and we'll come back to you with custom pricing.</p>

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm mb-6">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('contact.enterprise.submit') }}" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" value="{{ old('name') }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" value="{{ old('email') }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="company" value="Company (optional)" />
                    <x-text-input id="company" name="company" class="block mt-1 w-full" value="{{ old('company') }}" />
                    <x-input-error :messages="$errors->get('company')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="message" value="What are you looking for?" />
                    <textarea id="message" name="message" rows="5" class="block mt-1 w-full border-gray-300 focus:border-vale-navy focus:ring-vale-navy rounded-md shadow-sm" required>{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                <x-primary-button class="w-full justify-center">
                    Send Enquiry
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
