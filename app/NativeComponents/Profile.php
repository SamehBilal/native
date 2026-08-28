<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Profile extends NativeComponent
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'customer';

    public bool $loading = true;

    public bool $saving = false;

    public ?string $error = null;

    public ?string $saved = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $result = $api->me();
        $this->loading = false;

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->name = $result['data']['name'] ?? '';
        $this->email = $result['data']['email'] ?? '';
        $this->phone = $result['data']['phone'] ?? '';
        $this->role = $result['data']['role'] ?? 'customer';
    }

    public function save(): void
    {
        $this->saving = true;
        $this->saved = null;
        $this->error = null;

        $result = app(MarketplaceApi::class)->updateProfile([
            'name' => $this->name,
            'phone' => $this->phone,
        ]);

        $this->saving = false;

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->saved = 'Profile updated.';
    }

    public function goToHome(): void
    {
        $this->navigate($this->role === 'provider' ? '/app/provider-dashboard' : '/app/explore');
    }

    public function logout(): void
    {
        app(MarketplaceApi::class)->logout();
        $this->replace('/app/login');
    }

    public function render(): View
    {
        return view('native.profile');
    }
}
