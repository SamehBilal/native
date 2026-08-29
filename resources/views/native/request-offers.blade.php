<native:top-bar title="Your Request" subtitle="{{ ucfirst(str_replace('_', ' ', $serviceType)) }}" back />

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        @if ($loading)
            <native:activity-indicator />
        @else
            <native:column class="w-full p-3 bg-white border border-gray-200 rounded-2xl">
                <native:text class="font-bold capitalize text-gray-900">Status: {{ $status }}</native:text>
                <native:text class="text-gray-500">Waiting for providers nearby to send you their best offer.</native:text>
            </native:column>

            @if ($error)
                <native:text class="text-red-600">{{ $error }}</native:text>
            @endif

            <native:text class="text-lg font-bold text-gray-900 mt-2">Offers ({{ count($offers) }})</native:text>

            @forelse ($offers as $offer)
                <native:column key="offer-{{ $offer['id'] }}" class="w-full p-4 bg-white border border-gray-200 rounded-2xl gap-2">
                    <native:text class="font-bold text-gray-900">{{ $offer['provider']['name'] ?? 'Provider' }}</native:text>
                    <native:text class="text-gray-500">{{ $offer['provider']['vehicle_info'] ?? '' }} · ★ {{ $offer['provider']['rating'] ?? '-' }}</native:text>
                    <native:row class="gap-4">
                        <native:text class="text-lg font-bold text-gray-900">${{ $offer['fee'] }}</native:text>
                        <native:text class="text-gray-500">ETA {{ $offer['eta_minutes'] }} min</native:text>
                    </native:row>
                    @if (!empty($offer['message']))
                        <native:text class="text-gray-500">"{{ $offer['message'] }}"</native:text>
                    @endif

                    @if ($offer['status'] === 'pending' && $status === 'pending')
                        <native:button
                            label="{{ $acceptingOfferId === $offer['id'] ? 'Accepting…' : 'Accept this offer' }}"
                            :disabled="$acceptingOfferId !== null"
                            @tap="accept({{ $offer['id'] }})"
                            class="w-full mt-1"
                        />
                    @else
                        <native:text class="text-gray-500 capitalize">{{ $offer['status'] }}</native:text>
                    @endif
                </native:column>
            @empty
                <native:text class="text-gray-500">No offers yet — nearby providers are being notified.</native:text>
            @endforelse

            <native:text class="text-lg font-bold text-gray-900 mt-4">Nearest providers</native:text>
            @forelse ($nearbyProviders as $provider)
                <native:row key="nearby-{{ $provider['id'] }}" class="w-full p-3 bg-white border border-gray-200 rounded-2xl justify-between">
                    <native:text class="text-gray-900">{{ $provider['name'] }}</native:text>
                    <native:text class="text-gray-500">{{ $provider['distance_km'] }} km</native:text>
                </native:row>
            @empty
                <native:text class="text-gray-500">No available providers found nearby.</native:text>
            @endforelse
        @endif
    </native:column>
</native:scroll-view>
