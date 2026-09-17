<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <flux:text class="text-center">
            {{ __('Click the link we just emailed you.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            {{-- Said no address. There is no registration here, so the starter
                 kit's "the email address you provided during registration" named
                 a step that never happened: `fleet:invite` made the account. --}}
            <flux:text class="text-center font-medium !dark:text-(--ds-success) !text-(--ds-success)">
                {{ __('New link sent.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
