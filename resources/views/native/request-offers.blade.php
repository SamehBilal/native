<native:top-bar title="Your Request" subtitle="{{ ucfirst(str_replace('_', ' ', $serviceType)) }}" back />

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full gap-4 p-6">
        @if ($loading)
            <native:activity-indicator />
        @else
            <native:column class="w-full p-3 bg-theme-primary/10 rounded-xl">
                <native:text class="font-bold capitalize">Status: {{ $status }}</native:text>
                <native:text class="text-theme-outline">Waiting for providers nearby to send you their best offer.</native:text>
            </native:column>

            @if ($error)
                <native:text class="text-theme-error">{{ $error }}</native:text>
            @endif

            <native:text class="text-lg font-bold mt-2">Offers ({{ count($offers) }})</native:text>

            @forelse ($offers as $offer)
                <native:column key="offer-{{ $offer['id'] }}" class="w-full p-4 bg-theme-surface rounded-xl gap-2">
                    <native:text class="font-bold">{{ $offer['provider']['name'] ?? 'Provider' }}</native:text>
                    <native:text class="text-theme-outline">{{ $offer['provider']['vehicle_info'] ?? '' }} · ★ {{ $offer['provider']['rating'] ?? '-' }}</native:text>
                    <native:row class="gap-4">
                        <native:text class="text-lg font-bold text-theme-primary">${{ $offer['fee'] }}</native:text>
                        <native:text class="text-theme-outline">ETA {{ $offer['eta_minutes'] }} min</native:text>
                    </native:row>
                    @if (!empty($offer['message']))
                        <native:text class="text-theme-outline">"{{ $offer['message'] }}"</native:text>
                    @endif

                    @if ($offer['status'] === 'pending' && $status === 'pending')
                        <native:button
                            label="{{ $acceptingOfferId === $offer['id'] ? 'Accepting…' : 'Accept this offer' }}"
                            :disabled="$acceptingOfferId !== null"
                            @tap="accept({{ $offer['id'] }})"
                            class="w-full mt-1"
                        />
                    @else
                        <native:text class="text-theme-outline capitalize">{{ $offer['status'] }}</native:text>
                    @endif
                </native:column>
            @empty
                <native:text class="text-theme-outline">No offers yet — nearby providers are being notified.</native:text>
            @endforelse

            <native:text class="text-lg font-bold mt-4">Nearest providers</native:text>
            @forelse ($nearbyProviders as $provider)
                <native:row key="nearby-{{ $provider['id'] }}" class="w-full p-3 bg-theme-surface rounded-xl justify-between">
                    <native:text>{{ $provider['name'] }}</native:text>
                    <native:text class="text-theme-outline">{{ $provider['distance_km'] }} km</native:text>
                </native:row>
            @empty
                <native:text class="text-theme-outline">No available providers found nearby.</native:text>
            @endforelse
        @endif
    </native:column>
</native:scroll-view>
