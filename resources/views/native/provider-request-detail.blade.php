<native:top-bar title="Submit an Offer" back />

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        <native:column class="w-full p-4 bg-white border border-gray-200 rounded-2xl gap-1">
            <native:text class="font-bold capitalize text-gray-900">{{ str_replace('_', ' ', $request['service_type'] ?? '') }}</native:text>
            @if (!empty($request['description']))
                <native:text class="text-gray-500">{{ $request['description'] }}</native:text>
            @endif
            @if (isset($request['distance_km']))
                <native:text class="text-gray-900 font-semibold">{{ $request['distance_km'] }} km away</native:text>
            @endif
        </native:column>

        @if ($submitted)
            <native:column class="w-full items-center gap-3 p-4">
                <native:text class="text-lg font-bold text-green-600">Offer sent!</native:text>
                <native:text class="text-gray-500">We'll let you know if the customer accepts.</native:text>
                <native:button label="Back to requests" @tap="backToDashboard" class="w-full mt-2" />
            </native:column>
        @else
            <native:column class="w-full gap-2">
                <native:text class="text-gray-500">Your fee</native:text>
                <native:button-group :options="$feeLabels" native:model="feeIndex" class="w-full" />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="text-gray-500">Estimated arrival</native:text>
                <native:button-group :options="$etaLabels" native:model="etaIndex" class="w-full" />
            </native:column>

            <native:column class="w-full gap-2">
                <native:text class="text-gray-500">Message (optional)</native:text>
                <native:button-group :options="$messageLabels" native:model="messageIndex" class="w-full" />
            </native:column>

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
