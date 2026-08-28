<?php

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceType;
use Database\Factories\ProviderFactory;

test('a customer can create a service request', function () {
    $customer = User::factory()->create();

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/service-requests', [
        'service_type' => 'emergency_tow',
        'pickup_latitude' => 24.7136,
        'pickup_longitude' => 46.6753,
        'description' => 'Engine will not start.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.service_type', 'emergency_tow');

    expect(ServiceRequest::first())->user_id->toBe($customer->id);
});

test('a provider cannot create a service request', function () {
    $provider = User::factory()->provider()->create();

    $this->actingAs($provider, 'sanctum')->postJson('/api/v1/service-requests', [
        'service_type' => 'emergency_tow',
        'pickup_latitude' => 24.7136,
        'pickup_longitude' => 46.6753,
    ])->assertForbidden();
});

test('nearby providers are returned nearest-first and filtered by service type and availability', function () {
    $customer = User::factory()->create();
    $request = ServiceRequest::factory()->for($customer)->create([
        'service_type' => ServiceType::TireExchange,
        'pickup_latitude' => ProviderFactory::BASE_LATITUDE,
        'pickup_longitude' => ProviderFactory::BASE_LONGITUDE,
    ]);

    $far = Provider::factory()->offering(ServiceType::TireExchange)->atDistanceKm(10)->create();
    $near = Provider::factory()->offering(ServiceType::TireExchange)->atDistanceKm(1)->create();
    Provider::factory()->offering(ServiceType::EmergencyTow)->atDistanceKm(0.5)->create();
    Provider::factory()->offering(ServiceType::TireExchange)->atDistanceKm(0.2)->unavailable()->create();

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/service-requests/{$request->id}/nearby-providers");

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids->all())->toBe([$near->id, $far->id]);
});

test('a customer cannot view another customer\'s request', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $request = ServiceRequest::factory()->for($owner)->create();

    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/service-requests/{$request->id}")
        ->assertForbidden();
});

test('guests cannot create a service request', function () {
    $this->postJson('/api/v1/service-requests', [
        'service_type' => 'emergency_tow',
        'pickup_latitude' => 24.7136,
        'pickup_longitude' => 46.6753,
    ])->assertUnauthorized();
});
