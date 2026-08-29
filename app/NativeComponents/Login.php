<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Login extends NativeComponent
{
    /**
     * Type "customer" or "provider" to sign in as that demo account.
     * There is no real credential check — see MarketplaceApi::loginAsDemo().
     */
    public string $email = '';

    public bool $loading = false;

    public ?string $error = null;

    public function mount(): void
    {
        if (app(MarketplaceApi::class)->isLoggedIn()) {
            $this->redirectHome();
        }
    }

    public function submit(): void
    {
        $this->error = null;

        if ($this->email === '') {
            $this->error = 'Type "customer" or "provider" to continue.';

            return;
        }

        $this->loading = true;
        $result = app(MarketplaceApi::class)->loginAsDemo($this->email);
        $this->loading = false;

        if (! $result['ok']) {
            $this->error = $result['message'] ?? 'Could not log in.';

            return;
        }

        $this->redirectHome();
    }

    public function goToRegister(): void
    {
        $this->navigate('/app/register');
    }

    protected function redirectHome(): void
    {
        $this->replace(app(MarketplaceApi::class)->isProvider() ? '/app/provider-dashboard' : '/app/explore');
    }

    public function render(): View
    {
        return view('native.login');
    }
}
