<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;
use Native\Mobile\Facades\Geolocation;

class Tracking extends NativeComponent
{
    /** Fixed drawing surface (logical pixels) the schematic radar is projected onto — matches Tailwind's w-64/h-64 (256px). */
    const CANVAS_SIZE = 256;

    public int $requestId = 0;

    public bool $isProvider = false;

    public string $status = 'accepted';

    public array $customer = [];

    public array $provider = [];

    public ?float $customerLat = null;

    public ?float $customerLng = null;

    public ?float $providerLat = null;

    public ?float $providerLng = null;

    public ?float $distanceKm = null;

    public array $messages = [];

    public string $newMessage = '';

    public bool $sending = false;

    public ?string $error = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $this->requestId = (int) $this->param('id');
        $this->isProvider = $api->isProvider();

        $details = $api->showServiceRequest($this->requestId);

        if ($details['ok']) {
            $this->customer = $details['data']['customer'] ?? [];
            $this->provider = $details['data']['accepted_provider'] ?? [];
            $this->status = $details['data']['status'];
        }

        $this->refresh();
    }

    #[Poll(3000)]
    public function refresh(): void
    {
        $api = app(MarketplaceApi::class);

        if ($this->isProvider) {
            Geolocation::getCurrentPosition()->locationReceived(function ($event) use ($api) {
                if ($event->success) {
                    $api->pushProviderLocation($event->latitude, $event->longitude);
                }
            });
        } else {
            Geolocation::getCurrentPosition()->locationReceived(function ($event) use ($api) {
                if ($event->success) {
                    $api->updateMyLocation($this->requestId, $event->latitude, $event->longitude);
                }
            });
        }

        $tracking = $api->track($this->requestId);

        if ($tracking['ok']) {
            $this->status = $tracking['data']['status'];
            $this->customerLat = $tracking['data']['customer']['latitude'] ?? null;
            $this->customerLng = $tracking['data']['customer']['longitude'] ?? null;
            $this->providerLat = $tracking['data']['provider']['latitude'] ?? null;
            $this->providerLng = $tracking['data']['provider']['longitude'] ?? null;
            $this->distanceKm = $tracking['data']['distance_km'] ?? null;
        }

        $messages = $api->messages($this->requestId);

        if ($messages['ok']) {
            $this->messages = $messages['data'] ?? [];
        }
    }

    public function sendMessage(): void
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        $this->sending = true;
        $result = app(MarketplaceApi::class)->sendMessage($this->requestId, $this->newMessage);
        $this->sending = false;

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->newMessage = '';
        $this->refresh();
    }

    /**
     * Project both parties onto the fixed-size schematic radar, keeping
     * them within the canvas bounds regardless of the real distance.
     *
     * @return array{customer: array{x: float, y: float}, provider: array{x: float, y: float}}|null
     */
    public function radarPoints(): ?array
    {
        if ($this->customerLat === null || $this->providerLat === null) {
            return null;
        }

        $centerLat = ($this->customerLat + $this->providerLat) / 2;
        $centerLng = ($this->customerLng + $this->providerLng) / 2;

        // A fixed degrees-to-pixels scale keeps close pairs from collapsing
        // to a single point while clamping far-apart pairs to the canvas edge.
        $scale = 4000;
        $half = self::CANVAS_SIZE / 2;
        $pad = 30;

        $project = function (float $lat, float $lng) use ($centerLat, $centerLng, $scale, $half, $pad) {
            $x = $half + ($lng - $centerLng) * $scale;
            $y = $half - ($lat - $centerLat) * $scale;

            return [
                'x' => (float) max($pad, min(self::CANVAS_SIZE - $pad, $x)),
                'y' => (float) max($pad, min(self::CANVAS_SIZE - $pad, $y)),
            ];
        };

        return [
            'customer' => $project($this->customerLat, $this->customerLng),
            'provider' => $project($this->providerLat, $this->providerLng),
        ];
    }

    public function call(): void
    {
        $phone = $this->isProvider ? ($this->customer['phone'] ?? null) : ($this->provider['phone'] ?? null);

        if ($phone) {
            Browser::open('tel:'.$phone);
        }
    }

    public function render(): View
    {
        return view('native.tracking', [
            'radar' => $this->radarPoints(),
        ]);
    }
}
