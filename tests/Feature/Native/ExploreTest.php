<?php

use App\MarketplaceApi;
use App\NativeComponents\{Explore, RequestOffers};
use Native\Mobile\Events\Geolocation\LocationReceived;
use Native\Mobile\Testing\Native;

test('creating a request with a chosen reason and budget shapes the offers that arrive', function () {
    app(MarketplaceApi::class)->loginAsDemo('customer');

    $test = Native::test(Explore::class);
    $test->call('chooseTireExchange');

    expect($test->instance()->serviceType)->toBe('tire_exchange');

    $test->set('reasonIndex', 1)
        ->set('budgetIndex', 2) // ~$60
        ->call('submit')
        ->emitNative(LocationReceived::class, ['success' => true, 'latitude' => 24.7, 'longitude' => 46.6]);

    expect($test->instance()->error)->toBeNull();

    $requestId = (int) str($test->instance()->getNavigationIntent()->uri)->afterLast('/')->toString();

    $result = app(MarketplaceApi::class)->showServiceRequest($requestId);

    expect($result['data']['budget'])->toBe(60);
});

test('a pending request can be cancelled from the waiting screen', function () {
    $api = app(MarketplaceApi::class);
    $api->loginAsDemo('customer');
    $requestId = $api->createServiceRequest([
        'service_type' => 'tire_exchange',
        'pickup_latitude' => 24.7,
        'pickup_longitude' => 46.6,
    ])['data']['id'];

    $test = Native::test(RequestOffers::class, params: ['id' => $requestId]);
    $test->call('cancel')->assertReplacedWith('/app/explore');

    expect($api->showServiceRequest($requestId)['data']['status'])->toBe('cancelled');
});
