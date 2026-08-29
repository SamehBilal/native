<native:column class="w-full h-full items-center justify-between p-8 bg-theme-background">
    <native:column class="w-full items-center gap-3 mt-16">
        <native:text class="text-3xl font-bold text-theme-primary">Road Assist</native:text>
        <native:text class="text-base text-theme-outline text-center">
            Stuck with a flat tire or a car that won't start? Get matched with the nearest
            available help in minutes.
        </native:text>
    </native:column>

    <native:column class="w-full gap-3 mb-6">
        <native:column class="w-full p-4 bg-theme-primary/10 rounded-xl gap-1">
            <native:text class="font-bold">Tire Exchange</native:text>
            <native:text class="text-theme-outline">Flat tire? Nearby providers send you their best price.</native:text>
        </native:column>
        <native:column class="w-full p-4 bg-theme-primary/10 rounded-xl gap-1">
            <native:text class="font-bold">Emergency Tow</native:text>
            <native:text class="text-theme-outline">Car won't move? Get towed to the nearest garage.</native:text>
        </native:column>
    </native:column>

    <native:column class="w-full gap-3">
        <native:button label="Get Started" @tap="getStarted" class="w-full" />
        <native:pressable @tap="logIn" class="items-center">
            <native:text class="text-theme-primary">Already have an account? Log In</native:text>
        </native:pressable>
    </native:column>
</native:column>
