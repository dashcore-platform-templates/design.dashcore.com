<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        {{-- Says what happens next. The old line described the field directly
             beneath it, which is the one thing the page already shows. --}}
        <x-auth-header :title="__('Forgot password')" :description="__('We will email you a link to set a new one.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-(--ds-text-muted)">
            <span>{{ __('Or') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
