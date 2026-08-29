<native:column class="w-full h-full items-center justify-center gap-4 p-6 bg-gray-50">
    <native:text class="text-2xl font-bold text-gray-900 tracking-tight">Road Assist</native:text>
    <native:text class="text-base text-gray-500">Demo mode — type "customer" or "provider" to continue</native:text>

    <native:column class="w-full gap-3 mt-4">
        <native:outlined-text-input
            placeholder="customer or provider"
            native:model="email"
            class="w-full"
        />

        @if ($error)
            <native:text class="text-red-600">{{ $error }}</native:text>
        @endif

        <native:button
            label="{{ $loading ? 'Signing in…' : 'Sign In' }}"
            :disabled="$loading"
            @tap="submit"
            class="w-full mt-2"
        />

        <native:pressable @tap="goToRegister" class="items-center mt-2">
            <native:text class="text-gray-900 font-semibold underline">New here? Create an account</native:text>
        </native:pressable>
    </native:column>
</native:column>
