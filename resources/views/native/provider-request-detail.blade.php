<native:top-bar title="Submit an Offer" back />

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        <native:column class="w-full p-4 bg-white rounded-xl gap-1">
            <native:text class="font-bold capitalize">{{ str_replace('_', ' ', $request['service_type'] ?? '') }}</native:text>
            @if (!empty($request['description']))
                <native:text class="text-gray-500">{{ $request['description'] }}</native:text>
            @endif
            @if (isset($request['distance_km']))
                <native:text class="text-blue-600">{{ $request['distance_km'] }} km away</native:text>
            @endif
        </native:column>

        @if ($submitted)
            <native:column class="w-full items-center gap-3 p-4">
                <native:text class="text-lg font-bold text-green-600">Offer sent!</native:text>
                <native:text class="text-gray-500">We'll let you know if the customer accepts.</native:text>
                <native:button label="Back to requests" @tap="backToDashboard" class="w-full mt-2" />
            </native:column>
        @else
            <native:outlined-text-input placeholder="Your fee (e.g. 60)" keyboard="decimal" native:model="fee" class="w-full" />
            <native:outlined-text-input placeholder="ETA in minutes (e.g. 15)" keyboard="number" native:model="etaMinutes" class="w-full" />
            <native:outlined-text-input placeholder="Message to the customer (optional)" native:model="message" class="w-full" />

            @if ($error)
                <native:text class="text-red-600">{{ $error }}</native:text>
            @endif

            <native:button
                label="{{ $submitting ? 'Sending…' : 'Send Offer' }}"
                :disabled="$submitting"
                @tap="submit"
                class="w-full mt-2"
            />
        @endif
    </native:column>
</native:scroll-view>
