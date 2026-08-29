<?php

use App\NativeComponents\Login;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    Http::fake([
        '*/api/v1/login' => Http::response([
            'user' => ['id' => 1, 'name' => 'Jane', 'email' => 'jane@example.com', 'phone' => null, 'role' => 'customer', 'provider' => null],
            'token' => '1|test-token',
        ], 200),
    ]);
});

test('a successful login clears any error and navigates to explore', function () {
    $store = [];

    FakeBridge::enable()
        ->respondTo('SecureStorage.Set', function ($params) use (&$store) {
            $store[$params['key']] = $params['value'];

            return ['success' => true];
        })
        ->respondTo('SecureStorage.Get', function ($params) use (&$store) {
            return array_key_exists($params['key'], $store)
                ? ['success' => true, 'value' => $store[$params['key']]]
                : ['success' => false];
        });

    $test = Native::test(Login::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->call('submit');

    expect($test->instance()->error)->toBeNull();

    $test->assertReplacedWith('/app/explore');
});

test('a login that cannot persist the session shows an error instead of navigating', function () {
    // No SecureStorage responses scripted: every SecureStorage.Set/Get call
    // resolves to a falsy bridge response, simulating a device (e.g. an
    // emulator without a lock-screen PIN) that rejects the Keystore write.
    FakeBridge::enable();

    $test = Native::test(Login::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->call('submit');

    expect($test->instance()->error)->not->toBeNull();

    $test->assertNoNavigation();
});
