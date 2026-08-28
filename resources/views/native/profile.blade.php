<native:top-bar title="Profile" />

@if ($role === 'provider')
    <native:bottom-nav>
        <native:bottom-nav-item id="requests" label="Requests" url="/app/provider-dashboard" icon="list.bullet" />
        <native:bottom-nav-item id="offers" label="My Offers" url="/app/provider-offers" icon="tag" />
        <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" active />
    </native:bottom-nav>
@else
    <native:bottom-nav>
        <native:bottom-nav-item id="explore" label="Explore" url="/app/explore" icon="map" />
        <native:bottom-nav-item id="profile" label="Profile" url="/app/profile" icon="person" active />
    </native:bottom-nav>
@endif

<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full items-center gap-4 p-6">
        @if ($loading)
            <native:activity-indicator />
        @else
            <native:column class="w-full gap-3">
                <native:text class="text-theme-outline">Email</native:text>
                <native:text class="text-lg">{{ $email }}</native:text>

                <native:text class="text-theme-outline mt-2">Role</native:text>
                <native:text class="text-lg capitalize">{{ $role }}</native:text>

                <native:text class="text-theme-outline mt-2">Name</native:text>
                <native:text-input native:model="name" class="w-full" />

                <native:text class="text-theme-outline mt-2">Phone</native:text>
                <native:text-input native:model="phone" class="w-full" />

                @if ($error)
                    <native:text class="text-theme-error">{{ $error }}</native:text>
                @endif
                @if ($saved)
                    <native:text class="text-theme-success">{{ $saved }}</native:text>
                @endif

                <native:button
                    label="{{ $saving ? 'Saving…' : 'Save Changes' }}"
                    :disabled="$saving"
                    @tap="save"
                    class="w-full mt-2"
                />

                <native:button label="Log Out" color="#DC2626" @tap="logout" class="w-full mt-4" />
            </native:column>
        @endif
    </native:column>
</native:scroll-view>
