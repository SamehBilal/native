<native:top-bar title="On The Way" subtitle="{{ ucfirst($status) }}" back />

<native:scroll-view class="w-full h-full bg-gray-50">
    <native:column class="w-full gap-4 p-6">
        <native:column class="w-full items-center bg-white rounded-xl overflow-hidden">
            @if ($mapHtml !== '')
                <native:webview :html="$mapHtml" javascript class="w-full h-72" />
                <native:row class="gap-4 p-3">
                    <native:text class="text-blue-500">● You</native:text>
                    <native:text class="text-orange-500">● Provider</native:text>
                    @if ($distanceKm !== null)
                        <native:text class="text-gray-500">{{ $distanceKm }} km apart</native:text>
                    @endif
                </native:row>
            @else
                <native:column class="w-full h-72 items-center justify-center gap-2">
                    <native:activity-indicator />
                    <native:text class="text-gray-500">Loading map…</native:text>
                </native:column>
            @endif
        </native:column>

        <native:row class="w-full p-4 bg-white rounded-xl justify-between items-center">
            <native:column>
                <native:text class="font-bold">
                    {{ $isProvider ? ($customer['name'] ?? 'Customer') : ($provider['name'] ?? 'Provider') }}
                </native:text>
                <native:text class="text-gray-500">
                    {{ $isProvider ? ($customer['phone'] ?? '') : ($provider['phone'] ?? '') }}
                </native:text>
            </native:column>
            <native:button label="Call" @tap="call" />
        </native:row>

        <native:text class="text-lg font-bold mt-2">Chat</native:text>

        <native:column class="w-full gap-2">
            @forelse ($messages as $message)
                @php $fromMe = $message['sender_role'] === ($isProvider ? 'provider' : 'customer'); @endphp
                <native:column
                    key="msg-{{ $message['id'] }}"
                    class="p-3 rounded-xl {{ $fromMe ? 'bg-blue-600 items-end' : 'bg-white items-start' }}"
                >
                    <native:text class="{{ $fromMe ? 'text-white' : 'text-gray-900' }}">{{ $message['body'] }}</native:text>
                </native:column>
            @empty
                <native:text class="text-gray-500">No messages yet.</native:text>
            @endforelse
        </native:column>

        @if ($error)
            <native:text class="text-red-600">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>

<native:bottom-bar>
    <native:row class="w-full gap-2 items-center">
        <native:outlined-text-input placeholder="Message…" native:model="newMessage" class="flex-1" />
        <native:button label="Send" :disabled="$sending" @tap="sendMessage" />
    </native:row>
</native:bottom-bar>
