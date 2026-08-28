<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Login extends NativeComponent
{
    public string $email = '';

    public string $password = '';

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

        if ($this->email === '' || $this->password === '') {
            $this->error = 'Enter your email and password.';

            return;
        }

        $this->loading = true;
        $result = app(MarketplaceApi::class)->login($this->email, $this->password);
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
