<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            {{-- Both descriptions name the thing the person is holding, in the
                 words /account/security used when they set it up: "a six-digit
                 code from an authenticator app", and codes each good once. --}}
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Authentication code')"
                    :description="__('The six-digit code from your authenticator app.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery code')"
                    :description="__('One of the codes you saved when you turned two-factor on.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-6 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-6" x-ref="otp">
                            <flux:otp
                                x-model="code"
                                length="6"
                                name="code"
                                label="Authentication code"
                                label:sr-only
                                class="mx-auto"
                             />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-6">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                label="Recovery code"
                                label:sr-only
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <flux:text class="text-(--ds-danger)">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full"
                    >
                        {{ __('Continue') }}
                    </flux:button>
                </div>

                {{-- One link, no lead-in. "or you can login using a recovery
                     code" spent three words getting to the verb and used login
                     as one. --}}
                <div class="mt-6 text-sm leading-5 text-center">
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('Use a recovery code instead') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('Use an authenticator code instead') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
