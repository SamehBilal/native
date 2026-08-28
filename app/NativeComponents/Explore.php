<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Geolocation;

class Explore extends NativeComponent
{
    /**
     * Demo fallback coordinate (Riyadh) used when device location isn't
     * available yet, so the flow stays usable in the simulator/tests.
     */
    const FALLBACK_LATITUDE = 24.7136;

    const FALLBACK_LONGITUDE = 46.6753;

    public string $description = '';

    public bool $creating = false;

    public ?string $error = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        if ($api->isProvider()) {
            $this->replace('/app/provider-dashboard');
        }
    }

    public function requestTireExchange(): void
    {
        $this->createRequest('tire_exchange');
    }

    public function requestEmergencyTow(): void
    {
        $this->createRequest('emergency_tow');
    }

    protected function createRequest(string $serviceType): void
    {
        $this->creating = true;
        $this->error = null;

        Geolocation::getCurrentPosition()->locationReceived(function ($event) use ($serviceType) {
            $latitude = $event->success ? $event->latitude : self::FALLBACK_LATITUDE;
            $longitude = $event->success ? $event->longitude : self::FALLBACK_LONGITUDE;

            $result = app(MarketplaceApi::class)->createServiceRequest([
                'service_type' => $serviceType,
                'pickup_latitude' => $latitude,
                'pickup_longitude' => $longitude,
                'description' => $this->description !== '' ? $this->description : null,
            ]);

            $this->creating = false;

            if (! $result['ok']) {
                $this->error = $result['message'] ?? 'Could not create your request.';

                return;
            }

            $this->navigate('/app/request-offers/'.$result['data']['id']);
        });
    }

    public function render(): View
    {
        return view('native.explore');
    }
}
