<native:top-bar title="Explore" subtitle="What do you need help with?" />

<native:bottom-nav>
    <native:bottom-nav-item id="explore" label="Explore" url="/app/explore" icon="map" active />
    <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" />
</native:bottom-nav>

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        @if ($error)
            <native:text class="text-red-600">{{ $error }}</native:text>
        @endif

        @if ($serviceType === null)
            <native:column class="w-full gap-3">
                <native:pressable @tap="chooseTireExchange" class="w-full p-4 bg-white border border-gray-200 rounded-2xl gap-1">
                    <native:text class="text-lg font-bold text-gray-900">Tire Exchange</native:text>
                    <native:text class="text-gray-500">Flat or damaged tire — find the nearest available provider.</native:text>
                </native:pressable>

                <native:pressable @tap="chooseEmergencyTow" class="w-full p-4 bg-white border border-gray-200 rounded-2xl gap-1">
                    <native:text class="text-lg font-bold text-gray-900">Emergency Tow</native:text>
                    <native:text class="text-gray-500">Car won't move — get towed to the nearest maintenance center.</native:text>
                </native:pressable>
            </native:column>
        @else
            <native:row class="w-full items-center justify-between">
                <native:text class="text-lg font-bold text-gray-900 capitalize">{{ str_replace('_', ' ', $serviceType) }}</native:text>
                <native:pressable @tap="changeServiceType">
                    <native:text class="text-gray-500 underline">Change</native:text>
                </native:pressable>
            </native:row>

            <native:column class="w-full gap-2">
                <native:text class="text-gray-500">What's going on?</native:text>
                <native:button-group :options="$reasons" native:model="reasonIndex" class="w-full" />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="text-gray-500">Your budget</native:text>
                <native:button-group :options="$budgetLabels" native:model="budgetIndex" class="w-full" />
            </native:column>

            @if ($creating)
                <native:column class="w-full items-center gap-2 mt-2">
                    <native:activity-indicator />
                    <native:text class="text-gray-500">Finding your location and sending your request…</native:text>
                </native:column>
            @else
                <native:button label="Request Help" @tap="submit" class="w-full mt-2" />
            @endif
        @endif
    </native:column>
</native:scroll-view>
