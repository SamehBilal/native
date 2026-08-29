<native:top-bar title="My Offers" />

<native:bottom-nav>
    <native:bottom-nav-item id="requests" label="Requests" url="/app/provider-dashboard" icon="list.bullet" />
    <native:bottom-nav-item id="offers" label="My Offers" url="/app/provider-offers" icon="tag" active />
    <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" />
</native:bottom-nav>

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-3 p-6">
        @if ($loading)
            <native:activity-indicator />
        @elseif ($error)
            <native:text class="text-red-600">{{ $error }}</native:text>
        @elseif (empty($offers))
            <native:text class="text-gray-500">You haven't sent any offers yet.</native:text>
        @else
            @foreach ($offers as $offer)
                <native:pressable
                    key="myoffer-{{ $offer['id'] }}"
                    @tap="openTracking({{ $offer['service_request_id'] }})"
                    class="w-full p-4 bg-white rounded-xl gap-1"
                >
                    <native:row class="justify-between">
                        <native:text class="font-bold">${{ $offer['fee'] }} · {{ $offer['eta_minutes'] }} min</native:text>
                        <native:text class="capitalize text-gray-500">{{ $offer['status'] }}</native:text>
                    </native:row>
                </native:pressable>
            @endforeach
        @endif
    </native:column>
</native:scroll-view>
