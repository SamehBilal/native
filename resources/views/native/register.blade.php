<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full items-center gap-4 p-6">
        <native:text class="text-2xl font-bold text-blue-600">Create Account</native:text>

        <native:row class="w-full gap-2">
            <native:button
                label="I need help"
                color="{{ $role === 'customer' ? null : '#9CA3AF' }}"
                @tap="chooseCustomer"
                class="flex-1"
            />
            <native:button
                label="I provide help"
                color="{{ $role === 'provider' ? null : '#9CA3AF' }}"
                @tap="chooseProvider"
                class="flex-1"
            />
        </native:row>

        <native:column class="w-full gap-3 mt-2">
            <native:text-input placeholder="Full name" native:model="name" class="w-full" />
            <native:text-input placeholder="Email" keyboard-type="email" native:model="email" class="w-full" />
            <native:text-input placeholder="Password" secure native:model="password" class="w-full" />
            <native:text-input placeholder="Phone (optional)" native:model="phone" class="w-full" />

            @if ($role === 'provider')
                <native:text class="text-gray-500 mt-2">What can you help with?</native:text>
                <native:toggle label="Tire exchange" native:model="offersTireExchange" />
                <native:toggle label="Emergency tow" native:model="offersEmergencyTow" />
                <native:text-input placeholder="Vehicle info (e.g. Tow Truck - ABC-123)" native:model="vehicleInfo" class="w-full" />

                @if ($latitude === null)
                    <native:text class="text-gray-500">Fetching your current location…</native:text>
                @else
                    <native:text class="text-gray-500">Location captured ✓</native:text>
                @endif
            @endif

            @if ($error)
                <native:text class="text-red-600">{{ $error }}</native:text>
            @endif

            <native:button
                label="{{ $loading ? 'Creating…' : 'Create Account' }}"
                :disabled="$loading"
                @tap="submit"
                class="w-full mt-2"
            />

            <native:pressable @tap="goToLogin" class="items-center mt-2">
                <native:text class="text-blue-600">Already have an account? Sign in</native:text>
            </native:pressable>
        </native:column>
    </native:column>
</native:scroll-view>
