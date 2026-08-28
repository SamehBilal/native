<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Geolocation;

class ProviderDashboard extends NativeComponent
{
    public array $requests = [];

    public bool $loading = true;

    public ?string $error = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        if (! $api->isProvider()) {
            $this->replace('/app/explore');

            return;
        }

        $this->refresh();
        $this->loading = false;
    }

    #[Poll(5000)]
    public function refresh(): void
    {
        $api = app(MarketplaceApi::class);

        Geolocation::getCurrentPosition()->locationReceived(function ($event) use ($api) {
            if ($event->success) {
                $api->pushProviderLocation($event->latitude, $event->longitude);
            }
        });

        $result = $api->providerRequests();

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->requests = $result['data'] ?? [];
    }

    public function viewRequest(int $requestId): void
    {
        $request = collect($this->requests)->firstWhere('id', $requestId);

        $this->navigate('/app/provider-request-detail/'.$requestId, ['request' => $request]);
    }

    public function render(): View
    {
        return view('native.provider-dashboard');
    }
}
