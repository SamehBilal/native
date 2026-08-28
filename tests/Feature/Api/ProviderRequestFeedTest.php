<?php

use App\Models\Provider;
use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceType;
use Database\Factories\ProviderFactory;

test('a provider only sees pending requests matching their services within range, nearest first, excluding ones they already offered on', function () {
    $providerUser = User::factory()->provider()->create();
    $provider = Provider::factory()->for($providerUser)->offering(ServiceType::TireExchange)->create([
        'latitude' => ProviderFactory::BASE_LATITUDE,
        'longitude' => ProviderFactory::BASE_LONGITUDE,
    ]);

    $near = ServiceRequest::factory()->create([
        'service_type' => ServiceType::TireExchange,
        'pickup_latitude' => ProviderFactory::BASE_LATITUDE,
        'pickup_longitude' => ProviderFactory::BASE_LONGITUDE,
    ]);

    $far = ServiceRequest::factory()->create([
        'service_type' => ServiceType::TireExchange,
        'pickup_latitude' => ProviderFactory::BASE_LATITUDE + 0.02,
        'pickup_longitude' => ProviderFactory::BASE_LONGITUDE + 0.02,
    ]);

    // Wrong service type: never shown to this provider.
    ServiceRequest::factory()->create([
        'service_type' => ServiceType::EmergencyTow,
        'pickup_latitude' => ProviderFactory::BASE_LATITUDE,
        'pickup_longitude' => ProviderFactory::BASE_LONGITUDE,
    ]);

    // Already offered on: excluded even though it matches.
    $alreadyOffered = ServiceRequest::factory()->create([
        'service_type' => ServiceType::TireExchange,
        'pickup_latitude' => ProviderFactory::BASE_LATITUDE,
        'pickup_longitude' => ProviderFactory::BASE_LONGITUDE,
    ]);
    ServiceOffer::factory()->for($alreadyOffered, 'serviceRequest')->create(['provider_id' => $provider->id]);

    $response = $this->actingAs($providerUser, 'sanctum')
        ->getJson('/api/v1/provider/requests')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids->all())->toBe([$near->id, $far->id]);
});

test('a provider can see their own submitted offers', function () {
    $providerUser = User::factory()->provider()->create();
    $provider = Provider::factory()->for($providerUser)->create();
    $offer = ServiceOffer::factory()->create(['provider_id' => $provider->id]);

    $response = $this->actingAs($providerUser, 'sanctum')
        ->getJson('/api/v1/provider/offers')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$offer->id]);
});
