<?php

use App\NativeComponents\Login;
use Native\Mobile\Testing\Native;

test('typing "customer" signs in and navigates to explore', function () {
    $test = Native::test(Login::class)
        ->set('email', 'customer')
        ->call('submit');

    expect($test->instance()->error)->toBeNull();

    $test->assertReplacedWith('/app/explore');
});

test('typing "provider" signs in and navigates to the provider dashboard', function () {
    $test = Native::test(Login::class)
        ->set('email', 'PROVIDER one')
        ->call('submit');

    expect($test->instance()->error)->toBeNull();

    $test->assertReplacedWith('/app/provider-dashboard');
});

test('a keyword that matches neither demo account shows an error instead of navigating', function () {
    $test = Native::test(Login::class)
        ->set('email', 'anything else')
        ->call('submit');

    expect($test->instance()->error)->not->toBeNull();

    $test->assertNoNavigation();
});
