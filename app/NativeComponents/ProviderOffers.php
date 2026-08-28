<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class ProviderOffers extends NativeComponent
{
    public array $offers = [];

    public bool $loading = true;

    public ?string $error = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $this->refresh();
        $this->loading = false;
    }

    #[Poll(6000)]
    public function refresh(): void
    {
        $result = app(MarketplaceApi::class)->providerOffers();

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->offers = $result['data'] ?? [];
    }

    public function openTracking(int $requestId): void
    {
        $this->navigate('/app/tracking/'.$requestId);
    }

    public function render(): View
    {
        return view('native.provider-offers');
    }
}
