<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Geolocation;

class Register extends NativeComponent
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $phone = '';

    public string $role = 'customer';

    public bool $offersTireExchange = false;

    public bool $offersEmergencyTow = false;

    public string $vehicleInfo = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $loading = false;

    public ?string $error = null;

    public function mount(): void
    {
        if (app(MarketplaceApi::class)->isLoggedIn()) {
            $this->replace('/app/explore');
        }
    }

    public function chooseCustomer(): void
    {
        $this->role = 'customer';
    }

    public function chooseProvider(): void
    {
        $this->role = 'provider';

        Geolocation::getCurrentPosition()->locationReceived(function ($event) {
            if ($event->success) {
                $this->latitude = $event->latitude;
                $this->longitude = $event->longitude;
            }
        });
    }

    public function submit(): void
    {
        $this->error = null;

        if ($this->name === '' || $this->email === '' || $this->password === '') {
            $this->error = 'Please fill in your name, email, and password.';

            return;
        }

        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'role' => $this->role,
        ];

        if ($this->role === 'provider') {
            $serviceTypes = array_filter([
                $this->offersTireExchange ? 'tire_exchange' : null,
                $this->offersEmergencyTow ? 'emergency_tow' : null,
            ]);

            if ($serviceTypes === []) {
                $this->error = 'Choose at least one service you can provide.';

                return;
            }

            if ($this->latitude === null || $this->longitude === null) {
                $this->error = 'We need your current location to register as a provider.';

                return;
            }

            $payload['service_types'] = array_values($serviceTypes);
            $payload['latitude'] = $this->latitude;
            $payload['longitude'] = $this->longitude;
            $payload['vehicle_info'] = $this->vehicleInfo !== '' ? $this->vehicleInfo : null;
        }

        $this->loading = true;
        $result = app(MarketplaceApi::class)->register($payload);
        $this->loading = false;

        if (! $result['ok']) {
            $this->error = collect($result['data']['errors'] ?? [])->flatten()->first()
                ?? $result['message']
                ?? 'Could not create your account.';

            return;
        }

        $this->replace($this->role === 'provider' ? '/app/provider-dashboard' : '/app/explore');
    }

    public function goToLogin(): void
    {
        $this->replace('/app/login');
    }

    public function render(): View
    {
        return view('native.register');
    }
}
