<native:top-bar title="Explore" subtitle="What do you need help with?" />

<native:bottom-nav>
    <native:bottom-nav-item id="explore" label="Explore" url="/app/explore" icon="map" active />
    <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" />
</native:bottom-nav>

<native:column class="w-full h-full items-center gap-4 p-6 bg-gray-50">
    <native:text-input
        placeholder="Describe the problem (optional)"
        native:model="description"
        class="w-full"
    />

    @if ($error)
        <native:text class="text-red-600">{{ $error }}</native:text>
    @endif

    <native:column class="w-full gap-4 mt-4">
        <native:pressable @tap="requestTireExchange" class="w-full p-4 bg-blue-50 rounded-xl gap-1">
            <native:text class="text-lg font-bold">Tire Exchange</native:text>
            <native:text class="text-gray-500">Flat or damaged tire — find the nearest available provider.</native:text>
        </native:pressable>

        <native:pressable @tap="requestEmergencyTow" class="w-full p-4 bg-blue-50 rounded-xl gap-1">
            <native:text class="text-lg font-bold">Emergency Tow</native:text>
            <native:text class="text-gray-500">Car won't move — get towed to the nearest maintenance center.</native:text>
        </native:pressable>
    </native:column>

    @if ($creating)
        <native:activity-indicator class="mt-4" />
        <native:text class="text-gray-500">Finding your location and sending your request…</native:text>
    @endif
</native:column>
