<native:top-bar title="On The Way" subtitle="{{ ucfirst($status) }}" back />

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full gap-4 p-6">
        <native:column class="w-full items-center p-4 bg-theme-surface rounded-xl gap-2">
            @if ($radar)
                <native:stack class="w-64 h-64 bg-theme-primary/5 rounded-xl">
                    <native:line
                        from="{{ $radar['customer']['x'] }},{{ $radar['customer']['y'] }}"
                        to="{{ $radar['provider']['x'] }},{{ $radar['provider']['y'] }}"
                        class="border-theme-outline"
                    />
                    <native:circle left="{{ $radar['customer']['x'] - 8 }}" top="{{ $radar['customer']['y'] - 8 }}" class="w-4 h-4 bg-blue-500" />
                    <native:circle left="{{ $radar['provider']['x'] - 8 }}" top="{{ $radar['provider']['y'] - 8 }}" class="w-4 h-4 bg-orange-500" />
                </native:stack>
                <native:row class="gap-4 mt-1">
                    <native:text class="text-blue-500">● You</native:text>
                    <native:text class="text-orange-500">● Provider</native:text>
                </native:row>
                @if ($distanceKm !== null)
                    <native:text class="text-theme-outline">{{ $distanceKm }} km apart</native:text>
                @endif
            @else
                <native:activity-indicator />
                <native:text class="text-theme-outline">Waiting for both locations…</native:text>
            @endif
        </native:column>

        <native:row class="w-full p-4 bg-theme-surface rounded-xl justify-between items-center">
            <native:column>
                <native:text class="font-bold">
                    {{ $isProvider ? ($customer['name'] ?? 'Customer') : ($provider['name'] ?? 'Provider') }}
                </native:text>
                <native:text class="text-theme-outline">
                    {{ $isProvider ? ($customer['phone'] ?? '') : ($provider['phone'] ?? '') }}
                </native:text>
            </native:column>
            <native:button label="Call" @tap="call" />
        </native:row>

        <native:text class="text-lg font-bold mt-2">Chat</native:text>

        <native:column class="w-full gap-2">
            @forelse ($messages as $message)
                <native:column key="msg-{{ $message['id'] }}" class="w-full p-3 bg-theme-surface rounded-xl">
                    <native:text>{{ $message['body'] }}</native:text>
                </native:column>
            @empty
                <native:text class="text-theme-outline">No messages yet.</native:text>
            @endforelse
        </native:column>

        @if ($error)
            <native:text class="text-theme-error">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>

<native:bottom-bar>
    <native:row class="w-full gap-2 items-center">
        <native:text-input placeholder="Message…" native:model="newMessage" class="flex-1" />
        <native:button label="Send" :disabled="$sending" @tap="sendMessage" />
    </native:row>
</native:bottom-bar>
