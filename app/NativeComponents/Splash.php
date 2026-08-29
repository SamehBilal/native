<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Splash extends NativeComponent
{
    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/welcome');

            return;
        }

        // Verify the stored token is still valid before trusting the cached
        // role — it may have been revoked server-side since the last launch.
        $result = $api->me();

        if (! $result['ok']) {
            $api->logout();
            $this->replace('/app/welcome');

            return;
        }

        $this->replace($api->isProvider() ? '/app/provider-dashboard' : '/app/explore');
    }

    public function render(): View
    {
        return view('native.splash');
    }
}
