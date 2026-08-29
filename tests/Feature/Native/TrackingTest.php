<?php

use App\MarketplaceApi;
use App\NativeComponents\{ProviderRequestDetail, Tracking};
use Native\Mobile\Testing\Native;

test('the provider dashboard is seeded with example walk-in requests', function () {
    $api = app(MarketplaceApi::class);
    $api->loginAsDemo('provider');

    $requests = $api->providerRequests()['data'];

    expect($requests)->toHaveCount(3)
        ->and(collect($requests)->pluck('service_type')->unique()->all())->toContain('tire_exchange', 'emergency_tow');
});

test('bidding on a walk-in request auto-accepts and is immediately trackable with a map', function () {
    $api = app(MarketplaceApi::class);
    $api->loginAsDemo('provider');
    $api->providerRequests(); // seeds the walk-ins

    $test = Native::test(ProviderRequestDetail::class, params: ['id' => 9001], data: [
        'request' => ['id' => 9001, 'service_type' => 'tire_exchange', 'description' => 'test', 'distance_km' => 2.2],
    ]);

    $test->set('feeIndex', 1)->set('etaIndex', 1)->call('submit');

    expect($test->instance()->submitted)->toBeTrue();

    $test->call('checkAccepted')->assertNavigatedTo('/app/tracking/9001');

    $tracking = Native::test(Tracking::class, params: ['id' => 9001]);

    expect($tracking->instance()->mapHtml)->toContain('leaflet.js')
        ->and($tracking->instance()->customer['name'])->toBe('Dana Cole');
});

test('a customer only receives offers from providers who service the requested type', function () {
    $api = app(MarketplaceApi::class);
    $api->loginAsDemo('customer');

    $api->createServiceRequest([
        'service_type' => 'emergency_tow',
        'pickup_latitude' => 24.7136,
        'pickup_longitude' => 46.6753,
    ]);

    $nearby = $api->nearbyProviders(1)['data'];

    expect(collect($nearby)->pluck('name'))->not->toContain("Layla's Tire Service");
});
