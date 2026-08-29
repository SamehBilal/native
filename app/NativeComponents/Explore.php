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

    /**
     * Quick-pick reasons per service type — tapped instead of typed.
     *
     * @var array<string, list<string>>
     */
    const REASONS = [
        'tire_exchange' => ['Flat tire', 'Blown tire', "Won't hold air", 'Spare needed'],
        'emergency_tow' => ["Won't start", 'Overheated', 'Accident', 'Stuck / stranded'],
    ];

    /** Budget presets shown as a button group; null means "no preference". */
    const BUDGET_LABELS = ['Any price', '~$40', '~$60', '$80+'];

    /** @var list<int|null> */
    const BUDGET_VALUES = [null, 40, 60, 80];

    /** Null until the customer taps Tire Exchange or Emergency Tow. */
    public ?string $serviceType = null;

    public int $reasonIndex = 0;

    public int $budgetIndex = 0;

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

    public function chooseTireExchange(): void
    {
        $this->serviceType = 'tire_exchange';
        $this->reasonIndex = 0;
        $this->error = null;
    }

    public function chooseEmergencyTow(): void
    {
        $this->serviceType = 'emergency_tow';
        $this->reasonIndex = 0;
        $this->error = null;
    }

    public function changeServiceType(): void
    {
        $this->serviceType = null;
    }

    public function submit(): void
    {
        if ($this->serviceType === null) {
            return;
        }

        $this->creating = true;
        $this->error = null;

        $serviceType = $this->serviceType;
        $description = self::REASONS[$serviceType][$this->reasonIndex] ?? null;
        $budget = self::BUDGET_VALUES[$this->budgetIndex] ?? null;

        Geolocation::getCurrentPosition()->locationReceived(function ($event) use ($serviceType, $description, $budget) {
            $latitude = $event->success ? $event->latitude : self::FALLBACK_LATITUDE;
            $longitude = $event->success ? $event->longitude : self::FALLBACK_LONGITUDE;

            $result = app(MarketplaceApi::class)->createServiceRequest([
                'service_type' => $serviceType,
                'pickup_latitude' => $latitude,
                'pickup_longitude' => $longitude,
                'description' => $description,
                'budget' => $budget,
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
        return view('native.explore', [
            'reasons' => $this->serviceType !== null ? self::REASONS[$this->serviceType] : [],
            'budgetLabels' => self::BUDGET_LABELS,
        ]);
    }
}
