<native:top-bar title="Nearby Requests" />

<native:bottom-nav>
    <native:bottom-nav-item id="requests" label="Requests" url="/app/provider-dashboard" icon="list.bullet" active />
    <native:bottom-nav-item id="offers" label="My Offers" url="/app/provider-offers" icon="tag" />
    <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" />
</native:bottom-nav>

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full gap-3 p-6">
        @if ($loading)
            <native:activity-indicator />
        @elseif ($error)
            <native:text class="text-theme-error">{{ $error }}</native:text>
        @elseif (empty($requests))
            <native:text class="text-theme-outline">No matching requests nearby right now.</native:text>
        @else
            @foreach ($requests as $request)
                <native:pressable
                    key="request-{{ $request['id'] }}"
                    @tap="viewRequest({{ $request['id'] }})"
                    class="w-full p-4 bg-theme-surface rounded-xl gap-1"
                >
                    <native:text class="font-bold capitalize">{{ str_replace('_', ' ', $request['service_type']) }}</native:text>
                    @if (!empty($request['description']))
                        <native:text class="text-theme-outline">{{ $request['description'] }}</native:text>
                    @endif
                    <native:text class="text-theme-primary">{{ $request['distance_km'] }} km away</native:text>
                </native:pressable>
            @endforeach
        @endif
    </native:column>
</native:scroll-view>
