<native:column class="w-full h-full items-center justify-center gap-4 p-6 bg-theme-background">
    <native:text class="text-2xl font-bold text-theme-primary">Road Assist</native:text>
    <native:text class="text-base text-theme-outline">Sign in to request or provide roadside help</native:text>

    <native:column class="w-full gap-3 mt-4">
        <native:text-input
            placeholder="Email"
            keyboard-type="email"
            native:model="email"
            class="w-full"
        />
        <native:text-input
            placeholder="Password"
            secure
            native:model="password"
            class="w-full"
        />

        @if ($error)
            <native:text class="text-theme-error">{{ $error }}</native:text>
        @endif

        <native:button
            label="{{ $loading ? 'Signing in…' : 'Sign In' }}"
            :disabled="$loading"
            @tap="submit"
            class="w-full mt-2"
        />

        <native:pressable @tap="goToRegister" class="items-center mt-2">
            <native:text class="text-theme-primary">New here? Create an account</native:text>
        </native:pressable>
    </native:column>
</native:column>
