<?php

namespace App\NativeComponents;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Welcome extends NativeComponent
{
    public function getStarted(): void
    {
        $this->navigate('/app/register');
    }

    public function logIn(): void
    {
        $this->navigate('/app/login');
    }

    public function render(): View
    {
        return view('native.welcome');
    }
}
