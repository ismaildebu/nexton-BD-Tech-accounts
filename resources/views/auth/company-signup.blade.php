<x-guest-layout>
    <form method="POST" action="{{ route('company.register') }}">
        @csrf

        <h2 class="text-lg font-semibold text-gray-800 mb-4">
            {{ __('Create your company') }}
            <span class="block text-sm font-normal text-gray-500">{{ __('Starts on the Free plan.') }}</span>
        </h2>

        <!-- Company Name -->
        <div>
            <x-input-label for="company_name" :value="__('Company Name')" />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autofocus />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <!-- Business Type -->
        <div class="mt-4">
            <x-input-label for="business_type" :value="__('Business Type')" />
            <x-text-input id="business_type" class="block mt-1 w-full" type="text" name="business_type" :value="old('business_type')" required />
            <x-input-error :messages="$errors->get('business_type')" class="mt-2" />
        </div>

        <!-- Account Templates Selection -->
        <div class="mt-4">
            <x-input-label for="accounts" :value="__('Select Account Templates')" />
            <p class="text-sm text-gray-600 mt-1 mb-3">{{ __('Choose which accounts to create for your company') }}</p>
            
            <div class="space-y-2 border border-gray-300 rounded-md p-4 max-h-64 overflow-y-auto">
                @forelse($templates as $template)
                    <label class="flex items-center">
                        <input type="checkbox" name="accounts[]" value="{{ $template->id }}" 
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @if(in_array($template->id, old('accounts', []))) checked @endif />
                        <span class="ms-2 text-sm text-gray-700">
                            <strong>{{ $template->account_code }}</strong> - {{ $template->account_name }}
                            <span class="text-xs text-gray-500">({{ $template->account_type }})</span>
                        </span>
                    </label>
                @empty
                    <p class="text-gray-500 text-sm">{{ __('No account templates available') }}</p>
                @endforelse
            </div>
            <x-input-error :messages="$errors->get('accounts')" class="mt-2" />
            <x-input-error :messages="$errors->get('accounts.*')" class="mt-2" />
        </div>

        <hr class="my-6">

        <!-- Admin Name -->
        <div>
            <x-input-label for="admin_name" :value="__('Your Name')" />
            <x-text-input id="admin_name" class="block mt-1 w-full" type="text" name="admin_name" :value="old('admin_name')" required />
            <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
        </div>

        <!-- Admin Email -->
        <div class="mt-4">
            <x-input-label for="admin_email" :value="__('Email')" />
            <x-text-input id="admin_email" class="block mt-1 w-full" type="email" name="admin_email" :value="old('admin_email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
        </div>

        <!-- Admin Password -->
        <div class="mt-4">
            <x-input-label for="admin_password" :value="__('Password')" />
            <x-text-input id="admin_password" class="block mt-1 w-full" type="password" name="admin_password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="admin_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="admin_password_confirmation" class="block mt-1 w-full" type="password" name="admin_password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('admin_password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already have an account?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Create Company') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>