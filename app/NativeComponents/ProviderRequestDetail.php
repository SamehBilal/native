<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class ProviderRequestDetail extends NativeComponent
{
    public int $requestId = 0;

    public array $request = [];

    public string $fee = '';

    public string $etaMinutes = '';

    public string $message = '';

    public bool $submitting = false;

    public bool $submitted = false;

    public ?string $error = null;

    public function mount(): void
    {
        if (! app(MarketplaceApi::class)->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $this->requestId = (int) $this->param('id');
        $this->request = $this->data('request', []);
    }

    public function submit(): void
    {
        $this->error = null;

        if ($this->fee === '' || $this->etaMinutes === '') {
            $this->error = 'Enter your fee and estimated arrival time.';

            return;
        }

        $this->submitting = true;

        $result = app(MarketplaceApi::class)->submitOffer($this->requestId, [
            'fee' => (float) $this->fee,
            'eta_minutes' => (int) $this->etaMinutes,
            'message' => $this->message !== '' ? $this->message : null,
        ]);

        $this->submitting = false;

        if (! $result['ok']) {
            $this->error = $result['message'] ?? 'Could not submit your offer.';

            return;
        }

        $this->submitted = true;
    }

    #[Poll(5000)]
    public function checkAccepted(): void
    {
        if (! $this->submitted) {
            return;
        }

        $offers = app(MarketplaceApi::class)->providerOffers();

        if (! $offers['ok']) {
            return;
        }

        $mine = collect($offers['data'])->first(
            fn (array $offer) => $offer['service_request_id'] === $this->requestId
        );

        if ($mine && $mine['status'] === 'accepted') {
            $this->navigate('/app/tracking/'.$this->requestId);
        }
    }

    public function backToDashboard(): void
    {
        $this->replace('/app/provider-dashboard');
    }

    public function render(): View
    {
        return view('native.provider-request-detail');
    }
}
