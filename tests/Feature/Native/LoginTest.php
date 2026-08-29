<?php

use App\NativeComponents\Login;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    Http::fake(function ($request) {
        $email = $request['email'];
        $role = str_contains($email, 'provider') ? 'provider' : 'customer';

        return Http::response([
            'user' => ['id' => 1, 'name' => 'Demo', 'email' => $email, 'phone' => null, 'role' => $role, 'provider' => null],
            'token' => "1|test-token-{$role}",
        ], 200);
    });
});

test('typing "customer" signs in and navigates to explore', function () {
    $test = Native::test(Login::class)
        ->set('email', 'customer')
        ->call('submit');

    expect($test->instance()->error)->toBeNull();

    $test->assertReplacedWith('/app/explore');

    Http::assertSent(fn ($request) => $request['email'] === 'customer@example.com');
});

test('typing "provider" signs in and navigates to the provider dashboard', function () {
    $test = Native::test(Login::class)
        ->set('email', 'PROVIDER one')
        ->call('submit');

    expect($test->instance()->error)->toBeNull();

    $test->assertReplacedWith('/app/provider-dashboard');

    Http::assertSent(fn ($request) => $request['email'] === 'provider1@example.com');
});

test('a keyword that matches neither demo account shows an error instead of navigating', function () {
    $test = Native::test(Login::class)
        ->set('email', 'anything else')
        ->call('submit');

    expect($test->instance()->error)->not->toBeNull();

    $test->assertNoNavigation();
    Http::assertNothingSent();
});
