<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class RequestOffers extends NativeComponent
{
    public int $requestId = 0;

    public string $status = 'pending';

    public string $serviceType = '';

    public ?int $budget = null;

    public array $offers = [];

    public array $nearbyProviders = [];

    public bool $loading = true;

    /** Ticks up every poll while pending, so the wait visibly feels live. */
    public int $secondsWaiting = 0;

    public ?string $error = null;

    public ?int $acceptingOfferId = null;

    public bool $cancelling = false;

    public function mount(): void
    {
        if (! app(MarketplaceApi::class)->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $this->requestId = (int) $this->param('id');
        $this->loadNearbyProviders();
        $this->refresh();
        $this->loading = false;
    }

    #[Poll(4000)]
    public function refresh(): void
    {
        $result = app(MarketplaceApi::class)->showServiceRequest($this->requestId);

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->status = $result['data']['status'];
        $this->serviceType = $result['data']['service_type'];
        $this->budget = $result['data']['budget'] ?? null;
        $this->offers = $result['data']['offers'] ?? [];

        if ($this->status === 'pending') {
            $this->secondsWaiting += 4;
        }

        if ($this->status === 'accepted') {
            $this->navigate('/app/tracking/'.$this->requestId);
        }
    }

    protected function loadNearbyProviders(): void
    {
        $result = app(MarketplaceApi::class)->nearbyProviders($this->requestId);

        if ($result['ok']) {
            $this->nearbyProviders = $result['data'] ?? [];
        }
    }

    public function accept(int $offerId): void
    {
        $this->acceptingOfferId = $offerId;

        $result = app(MarketplaceApi::class)->acceptOffer($offerId);

        $this->acceptingOfferId = null;

        if (! $result['ok']) {
            $this->error = $result['message'] ?? 'Could not accept that offer.';

            return;
        }

        $this->navigate('/app/tracking/'.$this->requestId);
    }

    public function cancel(): void
    {
        $this->cancelling = true;

        app(MarketplaceApi::class)->cancelRequest($this->requestId);

        $this->replace('/app/explore');
    }

    public function render(): View
    {
        return view('native.request-offers');
    }
}
