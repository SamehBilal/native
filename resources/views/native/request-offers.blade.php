<native:top-bar title="Your Request" subtitle="{{ ucfirst(str_replace('_', ' ', $serviceType)) }}" back />

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        @if ($loading)
            <native:activity-indicator />
        @else
            <native:column class="w-full p-4 bg-white border border-gray-200 rounded-2xl gap-1">
                <native:row class="w-full items-center justify-between">
                    <native:text class="font-bold capitalize text-gray-900">Status: {{ $status }}</native:text>
                    @if ($status === 'pending')
                        <native:text class="text-gray-500">🟢 Live</native:text>
                    @endif
                </native:row>
                <native:text class="text-gray-500">
                    Budget: {{ $budget !== null ? '~$'.$budget : 'Any price' }}
                </native:text>
            </native:column>

            @if ($error)
                <native:text class="text-red-600">{{ $error }}</native:text>
            @endif

            @if ($status === 'pending' && count($offers) === 0)
                <native:column class="w-full items-center gap-2 p-6 bg-white border border-gray-200 rounded-2xl">
                    <native:activity-indicator />
                    <native:text class="font-bold text-gray-900">Searching nearby providers…</native:text>
                    <native:text class="text-gray-500">{{ count($nearbyProviders) }} providers notified · {{ $secondsWaiting }}s</native:text>
                </native:column>
            @endif

            @if (count($offers) > 0)
                <native:text class="text-lg font-bold text-gray-900 mt-2">Offers ({{ count($offers) }})</native:text>

                @foreach ($offers as $offer)
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
                @endforeach
            @endif

            <native:text class="text-lg font-bold text-gray-900 mt-4">Nearest providers</native:text>
            @forelse ($nearbyProviders as $provider)
                <native:row key="nearby-{{ $provider['id'] }}" class="w-full p-3 bg-white border border-gray-200 rounded-2xl justify-between">
                    <native:text class="text-gray-900">{{ $provider['name'] }}</native:text>
                    <native:text class="text-gray-500">{{ $provider['distance_km'] }} km</native:text>
                </native:row>
            @empty
                <native:text class="text-gray-500">No available providers found nearby.</native:text>
            @endforelse

            @if ($status === 'pending')
                <native:button
                    label="{{ $cancelling ? 'Cancelling…' : 'Cancel request' }}"
                    variant="secondary"
                    :disabled="$cancelling"
                    @tap="cancel"
                    class="w-full mt-4"
                />
            @endif
        @endif
    </native:column>
</native:scroll-view>
