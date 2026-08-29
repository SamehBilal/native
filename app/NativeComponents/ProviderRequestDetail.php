<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

class ProviderRequestDetail extends NativeComponent
{
    /**
     * Fee presets, tailored per service type — tapped instead of typed.
     *
     * @var array<string, list<int>>
     */
    const FEE_VALUES = [
        'tire_exchange' => [40, 50, 60, 70, 80],
        'emergency_tow' => [80, 100, 120, 150],
    ];

    /** @var list<int> */
    const ETA_VALUES = [10, 15, 20, 30];

    /** @var list<string|null> */
    const MESSAGE_VALUES = [null, 'On my way!', "I'm nearby.", 'I have extra parts ready just in case.'];

    /** @var list<string> */
    const MESSAGE_LABELS = ['No message', 'On my way!', "I'm nearby", 'Extra parts ready'];

    public int $requestId = 0;

    public array $request = [];

    public int $feeIndex = 1;

    public int $etaIndex = 1;

    public int $messageIndex = 0;

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
        $this->submitting = true;

        $result = app(MarketplaceApi::class)->submitOffer($this->requestId, [
            'fee' => $this->feeValues()[$this->feeIndex],
            'eta_minutes' => self::ETA_VALUES[$this->etaIndex],
            'message' => self::MESSAGE_VALUES[$this->messageIndex],
        ]);

        $this->submitting = false;

        if (! $result['ok']) {
            $this->error = $result['message'] ?? 'Could not submit your offer.';

            return;
        }

        $this->submitted = true;
    }

    /** @return list<int> */
    public function feeValues(): array
    {
        return self::FEE_VALUES[$this->request['service_type'] ?? 'tire_exchange'] ?? self::FEE_VALUES['tire_exchange'];
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
        return view('native.provider-request-detail', [
            'feeLabels' => array_map(fn (int $fee) => "\${$fee}", $this->feeValues()),
            'etaLabels' => array_map(fn (int $min) => "{$min} min", self::ETA_VALUES),
            'messageLabels' => self::MESSAGE_LABELS,
        ]);
    }
}
