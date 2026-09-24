<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin - Subscription Plans</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-gray-500">
                Plans live here, not in code, so a price change or a new plan needs no deploy. "Group" is what
                gates trade-restricted report content (e.g. high-risk markers) - dealer requires an approved
                TraderVerification, pro doesn't. Sort order also defines upgrade (higher) vs downgrade (lower)
                between plans.
            </p>

            <div class="space-y-4">
                @foreach ($plans as $plan)
                    <form method="POST" action="{{ route('admin.subscription-plans.update', $plan) }}" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        @csrf
                        @method('PUT')
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <x-input-label value="Name" />
                                <x-text-input name="name" class="block mt-1 w-full" value="{{ old('name', $plan->name) }}" required />
                            </div>
                            <div>
                                <x-input-label value="Group" />
                                <select name="group" class="mt-1 block w-full rounded-md border-gray-300 focus:border-vale-red focus:ring-vale-red">
                                    <option value="pro" @selected(old('group', $plan->group) === 'pro')>Pro</option>
                                    <option value="dealer" @selected(old('group', $plan->group) === 'dealer')>Dealer</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Stripe price ID" />
                                <x-text-input name="stripe_price_id" class="block mt-1 w-full font-mono text-xs" value="{{ old('stripe_price_id', $plan->stripe_price_id) }}" placeholder="price_..." />
                            </div>
                            <div>
                                <x-input-label value="Sort order" />
                                <x-text-input type="number" name="sort_order" class="block mt-1 w-full" value="{{ old('sort_order', $plan->sort_order) }}" required />
                            </div>
                            <div>
                                <x-input-label value="Monthly price (ex. VAT)" />
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                    <x-text-input type="number" step="0.01" min="0.01" name="monthly_net" class="block w-full pl-7" value="{{ old('monthly_net', $plan->monthly_net) }}" required />
                                </div>
                            </div>
                            <div>
                                <x-input-label value="Monthly credits" />
                                <x-text-input type="number" name="monthly_credits" class="block mt-1 w-full" value="{{ old('monthly_credits', $plan->monthly_credits) }}" required />
                            </div>
                            <div>
                                <x-input-label value="Additional credit price (ex. VAT)" />
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                    <x-text-input type="number" step="0.01" min="0.01" name="additional_credit_net" class="block w-full pl-7" value="{{ old('additional_credit_net', $plan->additional_credit_net) }}" required />
                                </div>
                            </div>
                            <div class="flex items-end gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="active_{{ $plan->id }}" name="is_active" value="1" @checked(old('is_active', $plan->is_active)) class="rounded border-gray-300 text-vale-red focus:ring-vale-red">
                                <x-input-label for="active_{{ $plan->id }}" value="Active" class="mb-0" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-primary-button type="submit">Save</x-primary-button>
                        </div>
                    </form>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Add a new plan</h3>
                <form method="POST" action="{{ route('admin.subscription-plans.store') }}">
                    @csrf
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <x-input-label value="Name" />
                            <x-text-input name="name" class="block mt-1 w-full" value="{{ old('name') }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Group" />
                            <select name="group" class="mt-1 block w-full rounded-md border-gray-300 focus:border-vale-red focus:ring-vale-red">
                                <option value="pro" @selected(old('group') === 'pro')>Pro</option>
                                <option value="dealer" @selected(old('group') === 'dealer')>Dealer</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Stripe price ID" />
                            <x-text-input name="stripe_price_id" class="block mt-1 w-full font-mono text-xs" value="{{ old('stripe_price_id') }}" placeholder="price_..." />
                        </div>
                        <div>
                            <x-input-label value="Sort order" />
                            <x-text-input type="number" name="sort_order" class="block mt-1 w-full" value="{{ old('sort_order') }}" required />
                        </div>
                        <div>
                            <x-input-label value="Monthly price (ex. VAT)" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                <x-text-input type="number" step="0.01" min="0.01" name="monthly_net" class="block w-full pl-7" value="{{ old('monthly_net') }}" required />
                            </div>
                            <x-input-error :messages="$errors->get('monthly_net')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Monthly credits" />
                            <x-text-input type="number" name="monthly_credits" class="block mt-1 w-full" value="{{ old('monthly_credits') }}" required />
                            <x-input-error :messages="$errors->get('monthly_credits')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Additional credit price (ex. VAT)" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                <x-text-input type="number" step="0.01" min="0.01" name="additional_credit_net" class="block w-full pl-7" value="{{ old('additional_credit_net') }}" required />
                            </div>
                            <x-input-error :messages="$errors->get('additional_credit_net')" class="mt-1" />
                        </div>
                        <div class="flex items-end gap-2">
                            <input type="checkbox" id="active_new" name="is_active" value="1" checked class="rounded border-gray-300 text-vale-red focus:ring-vale-red">
                            <x-input-label for="active_new" value="Active" class="mb-0" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-primary-button type="submit">Add plan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
